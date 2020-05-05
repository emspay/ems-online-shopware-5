<?php

namespace emspay\Components\Emspay;

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
        ];
    /**
     * Translator EMS statuses into Shopware statuses
     *
     */
    CONST EMS_TO_SHOPWARE_STATUSES =
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
    const PLUGIN_VERSION = '1.0.0';

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

    public function __construct(){
        require_once ("Library/vendor/autoload.php");
    }

    /**
     * create a gigner clinet instance
     *
     * @param string $apiKey
     * @param string $product
     * @param boolean $useBundle
     * @return \Ginger\ApiClient
     */
    protected function getGignerClinet($apiKey, $useBundle = false)
    {
        $ems = \Ginger\Ginger::createClient(
            self::GINGER_ENDPOINT,
            $apiKey,
            $useBundle ?
                [
                    CURLOPT_CAINFO => self::getCaCertPath()
                ] : []
        );

        return $ems;
    }

    /**
     *  function get Cacert.pem path
     */

    protected static function getCaCertPath(){
        return dirname(__FILE__).'/Library/assets/cacert.pem';
    }

    /**
     *  Get the Ginger Client using client configuration
     *
     * @param object $config
     * @return \Ginger\ApiClient
     */
    public function getClient($config)
    {
        return $this->getGignerClinet($config['emsonline_apikey'],$config['emsonline_bundle_cacert']);
    }

    /**
     * Get Currency Short Name
     * @return mixed
     */
    public function getCurrencyName(){
        return self::getBasket()['sCurrencyName'];
    }

    /**
     * Get ShopWare Order Number
     * @return mixed
     */
    public function getOrderNumber(){
        return Shopware()->Modules()->Order()->sGetOrderNumber();
    }

    /**
     * Get ShopWare user from order
     * @return mixed
     */
    public function getUser(){
        return Shopware()->Modules()->Admin()->sGetUserData();
    }

    /**
     * Get ShopWare basket from order
     * @return mixed
     */
    public function getBasket(){
        return Shopware()->Session()->sOrderVariables['sBasket'];
    }

    /**
     * get Transactions array
     * @param $payment
     * @return array
     */
    public function getTransactions($payment){
        return array_filter([
            array_filter([
                'payment_method' => $payment,
                'payment_method_details' => array_filter(['issuer_id' => $this->getIssuerId($payment)])
                ])
        ]);
    }

    /**
     * Get Return Url
     * @return string
     */

    public function getReturnUrl($controller){
        return $this->getProviderUrl($controller,'return');
    }

    /**
     * Get Issuer Id for iDEAL payment method
     * @return mixed
     */
    public function getIssuerId($payment){
        if ($payment != 'ideal') {return null;}
        return $_SESSION['ems_issuer_id'];
    }

    /**
     * Get Webhook Url
     * @param $user
     * @param $amount
     * @return string
     */
    public function getWebhookUrl($controller,$user,$amount){
        return $this->getProviderUrl($controller,'webhook'). $this->getUrlParameters($this->getOrderToken($amount));
    }

    /** Get user token
     * @return mixed
     */
    public function getOrderToken(){
        $amount = self::getBasket()['sAmount'];
        $service = Shopware()->Container()->get("emspay.service");
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
    public function getOrderLines($basket, $payment_name){
        if (!in_array($payment_name,['emspay_klarnapaylater','emspay_afterpay']))
        {
            return null;
        }

        $order_lines = array();
        foreach ($basket['content'] as $product){
            array_push($order_lines,
            [
                'name' => $product['articlename'],
                'type' => 'physical',
                'currency' => self::DEFAULT_CURRENCY,
                'amount' => self::getAmountInCents($product['amount']),
                'quantity' => (int)$product['quantity'],
                'vat_percentage' => (int)self::getAmountInCents($product['tax_rate']),
                'merchant_order_line_id' => $product['articleID']
            ]);
        }
        if ($basket['sShippingcostsWithTax']>0) {
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
    public function getShipingTypeInfo(){
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
    public function getProviderUrl($controller = '',$action = 'pay')
    {
        return Shopware()->Front()->Router()->assemble(['controller' => $controller, 'action' => $action]);
    }

    /**
     * Get the Shopware Billing Adress
     *
     * @param $info
     * @return array
     */

    protected function getBillingAdress($info){
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

    public function getIpOfTheServer(){
        return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
    }

    /**
     * Get Shopware Shop locale
     *
     * @param $locale
     * @return mixed
     */

    public function getLocaleLowerCode($locale){
        list($low,) = explode('_',$locale);
        return $low;
    }

    /**
     * Clear all data what remembered while order processing
     */
    public function clearEmsSession(){
        unset($_SESSION['emspay_birthday']);
        unset($_SESSION['ems_issuer_id']);
    }

    /**
     * Function creating customer array
     *
     * @param $info
     * @return array
     *
     */
    public function getCustomer($info){
       return array_filter([
           'gender' => $info['shippingaddress']['salutation'] == 'mr' ? 'male' : 'female',
           'birthdate' => $this->getBirthday($info['additional']['payment']['name']),
           'address_type' => 'customer',
           'country' => $info['additional']['country']['countryiso'],
           'email_address' => $info['additional']['user']['email'],
           'first_name' => $info['shippingaddress']['firstname'],
           'last_name' => $info['shippingaddress']['lastname'],
           'merchant_customer_id' => (string)$info['shippingaddress']['id'],
           'phone_numbers' => array_filter([$info['billingaddress']['phone'],
               $info['shippingaddress']['phone']]),
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

    private function getBirthday($payment){
       if (!empty($_SESSION['emspay_birthday']) && $payment == 'emspay_afterpay') {
           return ($_SESSION['emspay_birthday']);
               } else {
           if ($payment == 'emspay_afterpay') {
              return 'error';
           } else {
               return null;
           }

       }
    }

    /**
     *
     * Add description to order
     *
     * @param $info
     * @return string
     */

    public function getOrderDescription($orderId){
        $message = 'Your order %s at %s';
        return sprintf($message,(string) $orderId ,Shopware()->Shop()->getName());
    }

    /**
     * Get amount of the order in cents
     *
     * @param $amount
     * @return int
     */

   public function getAmountInCents($amount)
    {
        return (int) round ((float) $amount * 100);
    }
}
