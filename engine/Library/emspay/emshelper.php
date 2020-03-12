<?php

class EmsHelper
{
    /**
     * EMS Online ShopWare plugin version
     */
    const PLUGIN_VERSION = '1.0.0';

    /**
     * Default currency for Order
     */
    const DEFAULT_CURRENCY = 'EUR';

    /**
     * @var string
     */
    protected $paymentMethod;

    /**
     *  Default Ginger endpoint
     */

    const GINGER_ENDPOINT = 'https://api.online.emspay.eu';

    /**
     * EMS Online Order statuses
     */
    const EMS_STATUS_EXPIRED = 'expired';
    const EMS_STATUS_NEW = 'new';
    const EMS_STATUS_PROCESSING = 'processing';
    const EMS_STATUS_COMPLETED = 'completed';
    const EMS_STATUS_CANCELLED = 'cancelled';
    const EMS_STATUS_ERROR = 'error';
    const EMS_STATUS_CAPTURED = 'captured';

    /**
     * @param string $paymentMethod
     */
    public function __construct($paymentMethod){
        require_once ("ginger-php/vendor/autoload.php");
        $this->paymentMethod = $paymentMethod;
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
            EmsHelper::GINGER_ENDPOINT,
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
        return dirname(__FILE__).'/emspay/ginger-php/assets/cacert.pem';
    }

    /**
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
            'merchant_order_id' => $info['content'][0]['id'],
            'return_url' => 'unscribed',
            'description' => $this->getOrderDescription($info),
            'customer' => $this->getCustomer($info),
            'payment_info' => [],
            'issuer_id' => [],
            'order_lines' => [],
            'webhook_url' => [],
            'plugin_version' => []
        ];
    }

    protected function getBillingAdress($info){
        return array_filter([
            'address_type' => 'billing',
            'address' => implode("\n", array(
                    trim($info['billingaddress']['street']),
                    trim($info['billingaddress']['zipcode']),
                    trim($info['billingaddress']['city'])
                )
            ),
            $info['additional']['country']['countryiso']
        ]);
    }

    /**
     * @return mixed
     */

    protected function getIpOfTheServer(){
        return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
    }

    /**
     * @param $locale
     * @return mixed
     */

    protected function getLocaleLowerCode($locale){
        list($low,) = explode('_',$locale);
        return $low;
    }

    protected function getCustomer($info){
       return array_filter([
           'address_type' => 'customer',
           'country' => $info['additional']['country']['countryiso'],
           'email_address' => $info['additional']['user']['email'],
           'first_name' => $info['shippingaddress']['firstname'],
           'last_name' => $info['shippingaddress']['lastname'],
           'merchant_customer_id' => $info['shippingaddress']['id'],
           'phone_numbers' => array_filter([$info['billingaddress']['phone'],
               $info['shippingaddress']['phone']]),
           'address' => implode("\n", array(
                trim($info['shippingaddress']['street']),
                 trim($info['shippingaddress']['zipcode']),
                   trim($info['shippingaddress']['city'])
                )
           ),
           'locale' => self::getLocaleLowerCode($info['locale']),
           'ip_address' => self::getIpOfTheServer(),
           'additional_addresses' => self::getBillingAdress($info)
       ]);
    }

    /**
     * @param $info
     * @return string
     */

    protected function getOrderDescription($info){
        $message = 'Your order %s at %s';
        return sprintf($message,$info['content'][0]['id'],$info['shop_name']);
    }

    /**
     * @param $amount
     * @return int
     */

   protected function getAmountInCents($amount)
    {
        return (int) round ((float) $amount * 100);
    }
}
