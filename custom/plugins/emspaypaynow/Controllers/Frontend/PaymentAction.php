<?php

use emspaypaynow\Components\emspaypaynow\PaymentResponse;
use emspaypaynow\Components\emspaypaynow\PaymentService;

class Shopware_Controllers_Frontend_PaymentAction extends Shopware_Controllers_Frontend_Payment
{
    private $order_token;
    private $shopware_order_id;

    /**
     * @var EmsHelper
     */
    public $emsHelper;

    /**
     * @var \Ginger\Ginger
     */
    private $ems;

    /**
     * @var string
     */

    protected $config;

    CONST EMS_PAY_LIBRARY_NAME = 'emspay';

    CONST EMS_PAY_PLUGIN_NAME = 'emspaypaynow';

    public function preDispatch()
    {
        $plugin = $this->get('kernel')->getPlugins()[self::EMS_PAY_PLUGIN_NAME];

        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views');

        $lib = $this->get('kernel')->getPlugins()[self::EMS_PAY_LIBRARY_NAME];

        if (empty($lib)) {die('first install EMS Online Library');}

        require_once($lib->getPath()."/emshelper.php");

        $this->emsHelper = new \Helper\EmsHelper($this->getClassName());

        $this->config = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName(self::EMS_PAY_LIBRARY_NAME,Shopware()->Shop());

        $this->shopware_order_id = $this->getBasket()['content'][0]['sessionID'];

        $this->ems = $this->emsHelper->getClient($this->config);
    }

    /**
     * Index action method.
     *
     * Forwards to the correct action.
     */

    public function indexAction()
    {
        /**
         * Check if one of the payment methods is selected. Else return to default controller.
         */
        switch ($this->getPaymentShortName()) {
            case self::EMS_PAY_PLUGIN_NAME:
                return $this->redirect(['action' => 'direct', 'forceSecure' => true]);
            default:
                return $this->redirect(['action' => 'gateway', 'forceSecure' => true]);
        }
    }

    /**
     * Gateway action method.
     *
     * Collects the payment information and transmit it to the payment provider.
     */
    public function gatewayAction()
    {
        $providerUrl = $this->getProviderUrl();
        $this->View()->assign('gatewayUrl', $providerUrl . $this->getUrlParameters());

    }

    /**
     * Generate EMS Apple Pay.
     *
     * @param array
     * @return array
     */
    protected function createOrder(array $orderData)
    {
        return $this->ems->createOrder([
            'amount' => $orderData['amount'],                                // Amount in cents
            'currency' => $orderData['currency'],                            // Currency
            'description' => $orderData['description'],                      // Description
            'merchant_order_id' => (string) $orderData['merchant_order_id'], // Merchant Order Id
            'return_url' => $orderData['return_url'],                        // Return URL
            'customer' => $orderData['customer'],                            // Customer information
            'extra' => $orderData['plugin_version'],                         // Extra information
            'webhook_url' => $orderData['webhook_url'],                      // Webhook URL
        ]);
    }

    public function errorAction(){
        print_r("message");exit;
    }

    /**
     * Direct action method.
     *
     * Collects the payment information and transmits it to the payment provider.
     */
    public function directAction()
    {
        $emsOrderData = $this->emsHelper->getOrderData($this->completeOrderData());
        $emsOrder = $this->createOrder($emsOrderData);

        if ($emsOrder['status'] == 'error') {
            $this->redirect(['controller' => 'PaymentAction', 'action' => 'error']);
        }

        if (isset($emsOrder['order_url'])){
            $this->redirect($emsOrder['order_url']);
        } else {
            print_r("Error processing order");
        }
    }

    /**
     * Return action method
     *
     * Reads the transactionResult and represents it for the customer.
     */
    public function returnAction()
    {
        $service = $this->container->get("emspaypaynow.paymentservice");
        $user = $this->getUser();
        $billing = $user['billingaddress'];
        $token = $service->createPaymentToken($this->getAmount(), $billing['customernumber']);

        if (!isset($this->emsHelper)) {
            $this->emsHelper = new EmsHelper($this->getClassName());
        }
        $ems_order = $this->ems->getOrder($_GET['order_id']);
        switch ($ems_order['status']) {
            case 'completed':
                $this->saveOrder(
                    $ems_order['transactions'][0]['id'],
                    $token,
                    $this->emsHelper::EMS_TO_SHOPWARE_STATUSES[$ems_order['status']]
                );
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            case 'cancelled':
                $this->forward('cancel');
                break;
            default:
                return $this->redirect(['controller' => 'checkout']);
        }
    }

    public function webhookAction(){
        $input = json_decode(file_get_contents("php://input"), true);

        if ($input['event'] != 'status_changed') {
            return false;
        }

        try{
        $ems_orderID = $input['order_id'];
        $service = $this->container->get("emspaypaynow.paymentservice");
        $token = $service->createPaymentResponse($this->Request())->token;
        $emsOrder = $this->ems->getOrder($ems_orderID);
        } catch (Exception $exception) {
            die("Error getting data from webhook".$exception->getMessage());
        }

        try{
            $this->savePaymentStatus($emsOrder['transactions'][0]['id'],$token,9);
        } catch (Exception $exception){
            die("Error saving order using webhook action".$exception->getMessage());
        }

    }

    /**
     * Cancel action method
     */
    public function cancelAction()
    {

    }

    /**
     * Creates the url parameters
     */
    private function getUrlParameters()
    {
        $router = $this->Front()->Router();
        $user = $this->getUser();
        $billing = $user['billingaddress'];
        $parameter = [
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrencyShortName(),
            'firstName' => $billing['firstname'],
            'lastName' => $billing['lastname'],
            'returnUrl' => $router->assemble(['action' => 'return', 'forceSecure' => true]),
            'cancelUrl' => $router->assemble(['action' => 'cancel', 'forceSecure' => true]),
        ];

        return '?' . http_build_query($parameter);
    }

    /**
     * Returns the URL of the payment provider. This has to be replaced with the real payment provider URL
     *
     * @return string
     */
    protected function getProviderUrl($controller = '',$action = 'pay')
    {
        return $this->Front()->Router()->assemble(['controller' => $controller, 'action' => $action]);
    }

    protected function completeOrderData(){
        $webhook = $this->getProviderUrl('PaymentAction','webhook'). $this->getUrlParameters();
        $return_url = $this->getProviderUrl('PaymentAction','return');
        return array_merge($this->getBasket(),
            ['currency' => $this->getCurrencyShortName()],
            ['shop_name' => Shopware()->Shop()->getName()],
            $this->getUser(),
            ['locale' => Shopware()->Shop()->getLocale()->getLocale()],
            ['webhook_url' => $webhook],
            ['return_url' => $return_url]);
    }
}
