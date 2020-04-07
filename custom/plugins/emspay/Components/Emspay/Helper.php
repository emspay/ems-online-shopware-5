<?php

namespace emspay\Components\Emspay;

use Ginger\ApiClient;
use Ginger\Ginger;

class Helper
{
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
            'captured' => 12
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
        require_once ("ginger-php/vendor/autoload.php");
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
     * Regenerate sessionId for new orders
     * @return mixed
     */

    public function endOrderSession(){
        return Zend_Session::regenerateId();
    }

    /**
     *  function get Cacert.pem path
     */

    protected static function getCaCertPath(){
        return dirname(__FILE__).'/ginger-php/assets/cacert.pem';
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

    public function getOrderData(array $info){
        return [
            'amount' => self::getAmountInCents($info['content']['0']['amount']),
            'currency' => $info['currency'],
            'merchant_order_id' => (string)$info['content'][0]['id'],
            'return_url' => $info['return_url'],
            'description' => $this->getOrderDescription($info),
            'customer' => $this->getCustomer($info),
            'payment_info' => [],
            'issuer_id' => [],
            'order_lines' => [],
            'webhook_url' => $info['webhook_url'],
            'plugin_version' => ['plugin' => $this->getPluginVersion()],
        ];
    }

    /**
     * Generate EMS Apple Pay.
     *
     * @param array
     * @return array
     */
    public function createOrder(array $orderData, $ginger,$payment_method = null)
    {
        $preOrder = [
            'amount' => $orderData['amount'],                                // Amount in cents
            'currency' => $orderData['currency'],                            // Currency
            'description' => $orderData['description'],                      // Description
            'merchant_order_id' => (string) $orderData['merchant_order_id'], // Merchant Order Id
            'return_url' => $orderData['return_url'],                        // Return URL
            'customer' => $orderData['customer'],                            // Customer information
            'extra' => $orderData['plugin_version'],                         // Extra information
            'webhook_url' => $orderData['webhook_url'],                      // Webhook URL
        ];
        if ($payment_method!=null) {
            $preOrder = array_merge($preOrder, ['transactions' => [
                    'payment_method' => $payment_method
                ]]);
        }

        return $ginger->createOrder($preOrder);
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
            $info['additional']['country']['countryiso']
        ])];
    }

    /**
     * Get version of the plugin
     *
     * @return string
     */

    protected function getPluginVersion()
    {
        return sprintf('ShopWare v%s', self::PLUGIN_VERSION);
    }

    /**
     * Get IP of the remote server
     *
     * @return mixed
     */

    protected function getIpOfTheServer(){
        return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
    }

    /**
     * Get Shopware Shop locale
     *
     * @param $locale
     * @return mixed
     */

    protected function getLocaleLowerCode($locale){
        list($low,) = explode('_',$locale);
        return $low;
    }

    /**
     * Function creating customer array
     *
     * @param $info
     * @return array
     *
     */
    protected function getCustomer($info){
       return array_filter([
           'address_type' => 'customer',
           'country' => $info['additional']['country']['countryiso'],
           'email_address' => $info['additional']['user']['email'],
           'first_name' => $info['shippingaddress']['firstname'],
           'last_name' => $info['shippingaddress']['lastname'],
           'merchant_customer_id' => (string)$info['shippingaddress']['id'],
           'phone_numbers' => array_filter([$info['billingaddress']['phone'],
               $info['shippingaddress']['phone']]),
           'address' => implode("\n", array_filter(array(
                trim($info['shippingaddress']['street']),
                 trim($info['shippingaddress']['zipcode']),
                   trim($info['shippingaddress']['city'])
                )
               )
           ),
           'locale' => self::getLocaleLowerCode($info['locale']),
           'ip_address' => self::getIpOfTheServer(),
           'additional_addresses' => self::getBillingAdress($info)
       ]);
    }

    /**
     *
     * Add description to order
     *
     * @param $info
     * @return string
     */

    protected function getOrderDescription($info){
        $message = 'Your order %s at %s';
        return sprintf($message,$info['content'][0]['id'],$info['shop_name']);
    }

    /**
     * Get amount of the order in cents
     *
     * @param $amount
     * @return int
     */

   protected function getAmountInCents($amount)
    {
        return (int) round ((float) $amount * 100);
    }
}
