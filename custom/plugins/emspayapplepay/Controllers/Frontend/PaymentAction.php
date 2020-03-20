<?php

use emspayapplepay\Components\emspayapplepay\PaymentResponse;
use emspayapplepay\Components\emspayapplepay\ApplePayPaymentService;
require_once (realpath("engine/Library/emspay/emshelper.php"));

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

    CONST EMS_PAY_PLUGIN_NAME = 'emspayapplepay';

    public function preDispatch()
    {
        $plugin = $this->get('kernel')->getPlugins()[self::EMS_PAY_PLUGIN_NAME];

        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views');

        $this->emsHelper = new EmsHelper($this->getClassName());

        $config = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName(self::EMS_PAY_PLUGIN_NAME,Shopware()->Shop());

        $this->shopware_order_id = $this->getBasket()['content'][0]['sessionID'];

        $this->ems = $this->emsHelper->getClient($config);
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
            'transactions' => [
                [
                    'payment_method' => "apple-pay"
                ]
            ]
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

        if ($emsOrder['transactions'][0]['status'] == 'error') {
            $this->redirect(['controller' => 'PaymentAction', 'action' => 'error']);
        }

        if (isset($emsOrder['transactions'][0]['payment_url'])&&!array_key_exists('error',$emsOrder['transactions'][0])){
            if (!$this->emsHelper->save_shopware_order(
                [
                    'payment' => $emsOrder['transactions'][0]['order_id'],
                    'token' => $this->shopware_order_id,
                    'status' => $this->emsHelper::EMS_TO_SHOPWARE_STATUSES['processing']
                ],
                $this
            )) {
                $this->emsHelper->update_order_payment_id(
                    [
                        'payment' => $emsOrder['transactions'][0]['order_id'],
                        'token' => $this->shopware_order_id
                    ]
                );
                $this->emsHelper->update_shopware_order_payment_status([
                    'payment' => $emsOrder['transactions'][0]['order_id'],
                    'token' => $this->shopware_order_id,
                    'status' => $this->emsHelper::EMS_TO_SHOPWARE_STATUSES['processing']
                ],
                    $this
                );
            }
            $this->redirect($emsOrder['transactions'][0]['payment_url']);
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
        if (!isset($this->emsHelper)) {
            $this->emsHelper = new EmsHelper($this->getClassName());
        }
        $ems_order = $this->ems->getOrder($_GET['order_id']);

        if (empty($this->emsHelper->get_shopware_order_using_emspay_order($_GET['order_id']))) {
            $token = $this->shopware_order_id;
        } else {
            $token = $this->emsHelper->get_shopware_order_using_emspay_order($_GET['order_id']);
        }
        switch ($ems_order['status']) {
            case 'completed':
                $this->emsHelper->update_shopware_order_payment_status(
                    [
                        'payment' => $_GET['order_id'],
                        'token' => $token,
                        'status' => $this->emsHelper::EMS_TO_SHOPWARE_STATUSES['completed']
                    ],
                    $this
                );
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                $this->emsHelper->endOrderSession();
                break;
            default:
                $this->emsHelper->update_shopware_order_payment_status(
                    [
                        'payment' => $_GET['order_id'],
                        'token' => $token,
                        'status' => $this->emsHelper::EMS_TO_SHOPWARE_STATUSES['cancelled']
                    ],
                    $this);
                $this->redirect(['controller' => 'PaymentAction', 'action' => 'cancel']);
                break;
        }
    }

    public function webhookAction(){

        $request = $this->Request();

            $input = json_decode(file_get_contents("php://input"), true);
            $ems_orderID = $input['order_id'];


        try{
            $emsOrder = $this->ems->getOrder($ems_orderID);
        } catch (Exception $exception){
            die($exception->getMessage());
        }

        try {
            if ($emsOrder['status'] == 'new' || $emsOrder['status'] == 'expired') {
                $status = $this-$this->emsHelper::EMS_TO_SHOPWARE_STATUSES[$emsOrder['status']];

                $orderID = $this->emsHelper->get_shopware_order_using_emspay_order($ems_orderID);

                $parametrs = $this->emsHelper->get_main_ids($orderID,$ems_orderID,$status);

              var_dump($this->savePaymentStatus($parametrs['payment'],$parametrs['token'],$parametrs['status'])); exit;
            }
        } catch (Exception $exception) {
        print_r($exception->getMessage()); exit;
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
        /** @var ApplePayPaymentService $service */
        $service = $this->container->get('emspayapplepay.applepay_payment_service');
        $router = $this->Front()->Router();
        $user = $this->getUser();
        $billing = $user['billingaddress'];
        $order_token = $service->createPaymentToken($this->getAmount(), $billing['customernumber']);
        $parameter = [
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrencyShortName(),
            'firstName' => $billing['firstname'],
            'lastName' => $billing['lastname'],
            'returnUrl' => $router->assemble(['action' => 'return', 'forceSecure' => true]),
            'cancelUrl' => $router->assemble(['action' => 'cancel', 'forceSecure' => true]),
            'token' => $order_token
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
