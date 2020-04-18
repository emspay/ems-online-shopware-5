<?php
class Shopware_Controllers_Frontend_EmsPayApplePay extends Shopware_Controllers_Frontend_Payment
{
    /**
     * @var \emspay\Components\Emspay\Helper
     */
    public $emsHelper;

    /**
     * @var \Ginger\Ginger
     */
    private $ems;

    /**
     * @var string
     */
    CONST EMS_PAY_PLUGIN_NAME = 'emspay_applepay';

    public function preDispatch()
    {
        $plugin = $this->get('kernel')->getPlugins()[self::EMS_PAY_PLUGIN_NAME];

        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views');

        $this->emsHelper = $this->container->get('emspay.helper');

        $this->ems = $this->emsHelper->getClient($this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay',Shopware()->Shop()));
    }

    /**
     * Index action method.
     *
     * Forwards to the correct action.
     */

    public function indexAction()
    {
        return $this->redirect(['action' => 'direct', 'forceSecure' => true]);
    }

    /**
     * Direct action method.
     *
     * Create the order and transmits it to the payment provider.
     */
    public function directAction()
    {
        try{
            $emsOrder = $this->emsHelper->createOrder($this->completeOrderData(),$this->ems, 'apple-pay');
        } catch (Exception $exception) {
            print_r($exception->getMessage());exit;
        }

        if ($emsOrder['status'] == 'error') {
            print_r("Error while creating your EMS order , please try again later"); exit;
        }
        if ($emsOrder['status'] == 'canceled') {
            print_r("You order was cancelled, please try again later"); exit;
        }
        $this->redirect($emsOrder['transactions'][0]['payment_url']);
    }

    /** Get user token
     * @return mixed
     */
    public function getOrderToken(){
        $service = $this->container->get("emspay.service");
        $user = $this->getUser();
        $billing = $user['billingaddress'];
        return $service->createPaymentToken($this->getAmount(), $billing['customernumber']);
    }

    /**
     * Return action method
     *
     * Reads the transactionResult and represents it for the customer.
     */
    public function returnAction()
    {
        $ems_order = $this->ems->getOrder($_GET['order_id']);

        switch ($ems_order['status']) {
            case 'completed':
                $this->saveOrder(
                    $ems_order['id'],
                    $this->getOrderToken(),
                    $this->emsHelper::EMS_TO_SHOPWARE_STATUSES[$ems_order['status']]
                );
                return $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            default:
                return $this->redirect(['controller' => 'checkout']);
        }
    }

    /**
     * Webhook
     * @return bool
     */
    public function webhookAction(){
        $input = json_decode(file_get_contents("php://input"), true);

        if ($input['event'] != 'status_changed') {
            return false;
        }

        try{
            $ems_orderID = $input['order_id'];
            $service = $this->container->get("emspay.service");
            $token = $service->createPaymentResponse($this->Request())->token;
            $emsOrder = $this->ems->getOrder($ems_orderID);
        } catch (Exception $exception) {
            die("Error getting data from webhook".$exception->getMessage());
        }

        try{
            print_r($this->savePaymentStatus($emsOrder['id'],$token,$this->emsHelper::EMS_TO_SHOPWARE_STATUSES[$emsOrder['status']]));
        } catch (Exception $exception){
            die("Error saving order using webhook action".$exception->getMessage());
        }

    }

    /**
     * Former array with all required data using for creating EMS Order
     * @return array
     */
    protected function completeOrderData(){
        return array_merge(
            ['basket' => $this->getBasket()],
            ['user' => $this->getUser()],
            ['webhook_url' => $this->emsHelper->getProviderUrl('EmsPayApplePay','webhook'). $this->emsHelper->getUrlParameters($this->getOrderToken())],
            ['return_url' => $this->emsHelper->getProviderUrl('EmsPayApplePay','return')]
        );
    }
}