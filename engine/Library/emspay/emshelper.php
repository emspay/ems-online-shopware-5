<?php

class EmsHelper
{
    CONST EMS_TO_SHOPWARE_STATUSES =
        [
            'error' => 35,
            'expired' => 34,
            'cancelled' => 35,
            'open' => 0,
            'new' => 0,
            'captured' => 1,
            'processing' => 17,
            'see-transactions' => 3,
            'completed' => 12,
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
     * @var string
     */
    protected $paymentMethod;

    /**
     *  Default Ginger endpoint
     */

    const GINGER_ENDPOINT = 'https://api.online.emspay.eu';

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

    public function update_order_payment_id($parametrs){
        Shopware()->Db()->query("UPDATE s_order SET  temporaryID=? WHERE transactionID=?",[$parametrs['payment'],$parametrs['token']]);
    }

    /**
     *  return array
     */
    public function get_main_ids($token, $payment_id, $status){
        return [
            'token' => $token,
            'payment' => $payment_id,
            'status' => $status
        ];
    }

    /**
     * @param array $parametrs
     * @param $shopware_controler
     * @return string
     */

    public function save_shopware_order(array $parametrs, $shopware_controler){
        try{
            return $shopware_controler->saveOrder($parametrs['token'],$parametrs['payment'],$parametrs['status']);
        } catch (Exception $exception){
            return $exception->getMessage();
        }
    }

    /**
     * @param array $parametrs
     * @param $shopware_controler
     * @return bool|string
     */

    public function update_shopware_order_payment_status(array $parametrs,$shopware_controler){
        try{
            $shopware_controler->savePaymentStatus($parametrs['token'],$parametrs['payment'],$parametrs['status']);
            return true;
        } catch (Exception $exception) {
            return $exception->getMessage();
        }
    }
    /**
     * @param $order_id
     * @return mixed
     */

    public function get_shopware_order_using_emspay_order($order_id){
        $sql = "Select transactionID FROM s_order WHERE temporaryID=?";
        $transactionsID = Shopware()->Db()->fetchOne($sql, [
            $order_id,
        ]);
        return $transactionsID;
    }

    /**
     *  function get Cacert.pem path
     */

    protected static function getCaCertPath(){
        return dirname(__FILE__).'/ginger-php/assets/cacert.pem';
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
     * @return string
     */

    protected function getPluginVersion()
    {
        return sprintf('ShopWare v%s', self::PLUGIN_VERSION);
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
