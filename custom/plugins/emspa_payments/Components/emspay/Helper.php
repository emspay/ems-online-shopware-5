<?php

namespace emspa_payments\Components\emspay;

use Ginger\ApiClient;
use Ginger\Ginger;

class Helper
{
    /**
     *  Translator Shopware Payment Name into Ems Payment Names
     */
    const SHOPWARE_TO_EMS_PAYMENTS =
        [
            'applepay' => 'apple-pay',
            'klarnapaylater' => 'klarna-pay-later',
            'klarnapaynow' => 'klarna-pay-now',
            'paynow' => null,
            'ideal' => 'ideal',
            'afterpay' => 'afterpay',
            'amex' => 'amex',
            'bancontact' => 'bancontact',
            'banktransfer' => 'bank-transfer',
            'creditcard' => 'credit-card',
            'payconiq' => 'payconiq',
            'paypal' => 'paypal',
            'tikkiepaymentrequest' => 'tikkie-payment-request',
            'wechat' => 'wechat',
        ];
    /**
     * Translator EMS statuses into Shopware statuses
     *
     */
    const EMS_TO_SHOPWARE_STATUSES =
        [
            'error' => 35,
            'expired' => 35,
            'cancelled' => 35,
            'new' => 17,
            'processing' => 17,
            'completed' => 12,
            'see-transactions' => 21,
            'captured' => 12,
            'invoiced' => 10
        ];

    /**
     * EMS Online ShopWare plugin version
     */
    const PLUGIN_VERSION = '1.2.0';

    /**
     * Default currency for Order
     */
    const DEFAULT_CURRENCY = 'EUR';

    /**
     *  Default Ginger endpoint
     */

    const GINGER_ENDPOINT = 'https://api.online.emspay.eu';

    /**
     * Constructor of the class which includes ginger-php autoload
     */

    public function __construct()
    {
        require_once(__DIR__ . '/Library/vendor/autoload.php');
    }

    /**
     * create a Ginger Client instance
     *
     * @param string $apiKey
     * @param boolean $useBundle
     * @return ApiClient
     */
    protected function getGignerClinet(string $apiKey, $useBundle = false): ApiClient
    {
        return \Ginger\Ginger::createClient(
            self::GINGER_ENDPOINT,
            $apiKey,
            $useBundle ?
                [
                    CURLOPT_CAINFO => self::getCaCertPath()
                ] : []
        );
    }

    /**
     *  function get Cacert.pem path
     */

    protected static function getCaCertPath()
    {
        return dirname(__FILE__) . '/Library/assets/cacert.pem';
    }

    /**
     *  Get the Ginger Client using client configuration
     *
     * @param object $config
     * @return ApiClient
     */
    public function getClient($config, $method = null)
    {
        switch ($method) {
            case 'klarna-pay-later' :
                $api_key = $config['emsonline_test_api_klarna'] != "" ? $config['emsonline_test_api_klarna'] : $config['emsonline_apikey'];
                break;
            case 'afterpay' :
                $api_key = $config['emsonline_test_api_afterpay'] != "" ? $config['emsonline_test_api_afterpay'] : $config['emsonline_apikey'];
                break;
            default :
                $api_key = $config['emsonline_apikey'];
        }
        return $this->getGignerClinet($api_key, $config['emsonline_bundle_cacert']);
    }

    /**
     * Get Currency Short Name
     * @return mixed
     */
    public function getCurrencyName()
    {
        return self::getBasket()['sCurrencyName'];
    }

    /**
     * Get ShopWare Order Number
     * @return mixed
     */
    public function getOrderNumber()
    {
        return Shopware()->Modules()->Order()->sGetOrderNumber();
    }

    /**
     * Get ShopWare user from order
     * @return mixed
     */
    public function getUser()
    {
        return Shopware()->Modules()->Admin()->sGetUserData();
    }

    /**
     * Get ShopWare basket from order
     * @return mixed
     */
    public function getBasket()
    {
        return Shopware()->Session()->sOrderVariables['sBasket'];
    }

    /**
     * get Transactions array
     * @param $payment
     * @return array
     */
    public function getTransactions($payment): array
    {
        return array_filter([
            array_filter([
                'payment_method' => $payment,
                'payment_method_details' => array_filter(['issuer_id' => $this->getIssuerId($payment)])
            ])
        ]);
    }

    /**
     * Get Return Url
     * @param $controller
     * @return string
     */

    public function getReturnUrl($controller): string
    {
        return $this->getProviderUrl($controller, 'return');
    }

    /**
     * Get Issuer Id for iDEAL payment method
     * @param $payment
     * @return mixed
     */
    public function getIssuerId($payment)
    {
        if ($payment != 'ideal') {
            return null;
        }
        return $_SESSION['ems_issuer_id'];
    }

    /**
     * Get Webhook Url
     * @param $controller
     * @return string
     */
    public function getWebhookUrl($controller): string
    {
        return $this->getProviderUrl($controller, 'webhook') . $this->getUrlParameters($this->getOrderToken());
    }

    /** Get user token
     * @return mixed
     */
    public function getOrderToken()
    {
        $amount = self::getBasket()['sAmount'];
        $service = Shopware()->Container()->get("emspa_payments.service");
        $user = Shopware()->Modules()->Admin()->sGetUserData();
        $billing = $user['billingaddress'];
        return $service->createPaymentToken($user, $amount, $billing['customernumber']);
    }


    /**
     * Get Order Lines array
     * @param $products
     * @param $name
     * @return array|null
     */
    public function getOrderLines($basket, $payment_name)
    {
        if (!in_array($payment_name, ['emspa_payments_klarnapaylater', 'emspa_payments_afterpay'])) {
            return null;
        }

        $order_lines = array();
        foreach ($basket['content'] as $product) {
            array_push($order_lines,
                [
                    'name' => $product['articlename'],
                    'type' => 'physical',
                    'currency' => self::DEFAULT_CURRENCY,
                    'amount' => self::getAmountInCents($product['priceNumeric']),
                    'quantity' => (int)$product['quantity'],
                    'vat_percentage' => (int)self::getAmountInCents($product['tax_rate']),
                    'merchant_order_line_id' => (string)$product['articleID']
                ]);
        }
        if ($basket['sShippingcostsWithTax'] > 0) {
            $shiping = $this->getShipingTypeInfo();
            array_push($order_lines,
                [
                    'name' => (string)$shiping['name'],
                    'type' => 'shipping_fee',
                    'amount' => self::getAmountInCents($basket['sShippingcostsWithTax']),
                    'currency' => 'EUR',
                    'vat_percentage' => (int)$this->getAmountInCents($basket['sShippingcostsTax']),
                    'merchant_order_line_id' => (string)$shiping['id'],
                    'quantity' => 1,
                ]
            );
        }

        return !empty($order_lines) ? $order_lines : null;
    }

    /**
     * Get the current Shiping method information
     * @return mixed
     */
    public function getShipingTypeInfo()
    {
        return Shopware()->Modules()->Admin()->sGetPremiumDispatch(Shopware()->Session()->sDispatch);
    }

    /**
     * Creates the url parameters
     */
    public function getUrlParameters($token)
    {
        return '?' . http_build_query([
                'token' => $token
            ]);
    }

    /**
     * Returns the URL of the payment provider. This has to be replaced with the real payment provider URL
     *
     * @return string
     */
    public function getProviderUrl($controller = '', $action = 'pay')
    {
        return Shopware()->Front()->Router()->assemble(['controller' => $controller, 'action' => $action]);
    }

    /**
     * Get the Shopware Billing Adress
     *
     * @param $info
     * @return array
     */

    protected function getBillingAdress($info)
    {
        return [array_filter([
            'address_type' => 'billing',
            'address' => implode("\n", array(
                    trim($info['billingaddress']['street']),
                    trim($info['billingaddress']['zipcode']),
                    trim($info['billingaddress']['city'])
                )
            ),
            'country' => $info['additional']['country']['countryiso']
        ])];
    }

    /**
     * Get version of the plugin
     *
     * @return string
     */

    public function getPluginVersion()
    {
        return sprintf('ShopWare v%s', self::PLUGIN_VERSION);
    }

    /**
     * Get IP of the remote server
     *
     * @return mixed
     */

    public function getIpOfTheServer()
    {
        return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
    }

    /**
     * Get Shopware Shop locale
     *
     * @param $locale
     * @return mixed
     */

    public function getLocaleLowerCode($locale)
    {
        list($low,) = explode('_', $locale);
        return $low;
    }

    /**
     * Clear all data what remembered while order processing
     */
    public function clearEmsSession()
    {
        unset($_SESSION['emspa_payments_birthday']);
        unset($_SESSION['ems_issuer_id']);
    }

    /**
     * Function creating customer array
     *
     * @param $info
     * @return array
     *
     */
    public function getCustomer($info)
    {
        return array_filter([
            'gender' => $info['shippingaddress']['salutation'] == 'mr' ? 'male' : 'female',
            'birthdate' => $this->getBirthday($info['additional']['payment']['name']),
            'address_type' => 'customer',
            'country' => $info['additional']['country']['countryiso'],
            'email_address' => $info['additional']['user']['email'],
            'first_name' => $info['shippingaddress']['firstname'],
            'last_name' => $info['shippingaddress']['lastname'],
            'merchant_customer_id' => (string)$info['shippingaddress']['id'],
            'phone_numbers' => array_filter(
                [
                    $this->isDigits($info['billingaddress']['phone']),
                    $this->isDigits($info['shippingaddress']['phone'])
                ]),
            'address' => implode("\n", array_filter(array(
                        trim($info['shippingaddress']['additionalAddressLine1']),
                        trim($info['shippingaddress']['additionalAddressLine2']),
                        trim($info['shippingaddress']['street']),
                        trim($info['shippingaddress']['zipcode']),
                        trim($info['shippingaddress']['city'])
                    )
                )
            ),
            'locale' => self::getLocaleLowerCode(Shopware()->Shop()->getLocale()->getLocale()),
            'ip_address' => self::getIpOfTheServer(),
            'additional_addresses' => self::getBillingAdress($info)
        ]);
    }

    protected function isDigits($s, int $minDigits = 9, int $maxDigits = 14)
    {
        return preg_match('/^[0-9]{' . $minDigits . ',' . $maxDigits . '}\z/', $s) ? $s : null;
    }

    private function getBirthday($payment)
    {
        if ($payment != 'emspa_payments_afterpay') {
            return null;
        }
        if (empty($_SESSION['emspa_payments_birthday'])) {
            $_SESSION['ginger_warning_message'] = 'Error processing order with AfterPay Payment, Please insert birthday on page Payment Method Selection';
            throw new \Exception($_SESSION['ginger_warning_message']);
        }
        return $_SESSION['emspa_payments_birthday'];
    }

    /**
     *
     * Add description to order
     *
     * @param $info
     * @return string
     */

    public function getOrderDescription($orderId)
    {
        $message = 'Your order %s at %s';
        return sprintf($message, (string)$orderId, Shopware()->Shop()->getName());
    }

    /**
     * Get amount of the order in cents
     *
     * @param $amount
     * @return int
     */

    public function getAmountInCents($amount)
    {
        return (int)round((float)$amount * 100);
    }
}
