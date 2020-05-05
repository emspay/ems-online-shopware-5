<?php
class Shopware_Controllers_Frontend_Gateway extends Shopware_Controllers_Frontend_Payment
{
    /**
     * Name of Controller for Return and Webhook URL builders
     */
    CONST CONTROLLER_NAME = 'Gateway';

    /**
     * Helper class object
     * @var
     */
    protected $helper;

    /**
     * Ginger library object
     * @var
     */
    protected $ginger;

    public function preDispatch(){
        $plugin = $this->get('kernel')->getPlugins()['emspay'];

        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views');

        $this->helper = $this->container->get('emspay.helper');                                                                                   //Create Helper

        $this->ginger = $this->helper->getClient($this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay'));       //Create EMS
    }

    /**
     * Index action method.
     *
     * Forwards to the Gateway Controller
     */

    public function indexAction()
    {
        return  $this->forward('createOrder');
    }

    public function errorAction()
    {
        return $this->View()->assign('error_message', $_SESSION['error_message']);
    }

    /**
     * Generate EMS Apple Pay.
     *
     * @param array
     * @return array
     */
    public function createOrderAction(){
        try{
            $basket = $this->helper->getBasket();
            $user = $this->helper->getUser();

            $payment_method = $this->helper::SHOPWARE_TO_EMS_PAYMENTS[explode('emspay_',$user['additional']['payment']['name'])[1]];
            $use_webhook = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay')['emsonline_webhook'];

            $preOrder = array_filter([
                'amount' => $this->helper->getAmountInCents($basket['sAmount']),                                // Amount in cents
                'currency' => $this->helper->getCurrencyName(),                                                 // Currency
                'merchant_order_id' => $this->helper->getOrderNumber(),                                         // Merchant Order Id
                'description' => $this->helper->getOrderDescription($this->helper->getOrderNumber()),           // Description
                'customer' => $this->helper->getCustomer($user),                                                // Customer information
                'payment_info' => [],                                                                           // Payment info
                'order_lines' => $this->helper->getOrderLines($basket,$user['additional']['payment']['name']),  // Order Lines
                'transactions' => $this->helper->getTransactions($payment_method),                              // Transactions Array
                'return_url' => $this->helper->getReturnUrl(self::CONTROLLER_NAME),                             // Return URL
                'webhook_url' => $use_webhook ? $this->helper->getWebhookUrl(self::CONTROLLER_NAME,$user,$basket['sAmount']) : null,  // Webhook URL
                'extra' => ['plugin' => $this->helper->getPluginVersion()],                                     // Extra information
            ]);

            $ems_order = $this->ginger->createOrder($preOrder);
            $this->helper->clearEmsSession();

          } catch (Exception $exception) {
                print_r($exception->getMessage());exit;
            }

            if ($ems_order['status'] == 'error') {
                $_SESSION['error_message'] = current($ems_order['transactions'])['reason'];
                return $this->redirect(['controller' => 'Gateway', 'action' => 'error']);
            }

            if ($ems_order['status'] == 'cancelled') {
                print_r("You order was cancelled, please try again later"); exit;
            }
            if (isset($ems_order['order_url'])) {
                return $this->redirect($ems_order['order_url']);
            }
       return $this->Response()->setRedirect(current($ems_order['transactions'])['payment_url']);
    }

    /**
     * Return action method
     *
     * Reads the transactionResult and represents it for the customer.
     */
    public function returnAction()
    {
        $ems_order_id = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_STRING);
        $ems_order = $this->ginger->getOrder($ems_order_id);

        switch ($ems_order['status']) {
            case 'completed':
                $this->saveOrder(
                    $ems_order['id'],
                    $this->helper->getOrderToken(),
                    $this->helper::EMS_TO_SHOPWARE_STATUSES[$ems_order['status']]
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
            $emsOrder = $this->ginger->getOrder($ems_orderID);
        } catch (Exception $exception) {
            die("Error getting data from webhook".$exception->getMessage());
        }

        try{
            return ($this->savePaymentStatus($emsOrder['id'],$token,$this->helper::EMS_TO_SHOPWARE_STATUSES[$emsOrder['status']]));
        } catch (Exception $exception){
            die("Error saving order using webhook action".$exception->getMessage());
        }
    }
}