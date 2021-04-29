<?php

use emspa_payments\Components\emspay\Helper;

class Shopware_Controllers_Frontend_emspayGateway extends Shopware_Controllers_Frontend_Payment
{
    /**
     * Name of Controller for Return and Webhook URL builders
     */
    const CONTROLLER_NAME = 'emspayGateway';

    /**
     * Helper class object
     * @var Helper;
     */
    protected $helper;

    /**
     * Ginger library object
     * @var
     */
    protected $ginger;

    /**
     * Payment Method name
     * @var
     */
    protected $payment_method;

    public function preDispatch()
    {
        try {
            $plugin = $this->get('kernel')->getPlugins()['emspa_payments'];

            $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views');

            $this->helper = $this->container->get('emspa_payments.helper');                                                                                   //Create Helper

            $this->payment_method = $this->helper::SHOPWARE_TO_EMS_PAYMENTS[explode('emspa_payments_', $this->getUser()['additional']['payment']['name'])[1]];

            $this->ginger = $this->helper->getClient($this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('emspa_payments'), $this->payment_method);       //Create EMS
        } catch (Exception $exception) {
            echo "An error has occurred with the payment method. Please contact the store owner";
        }
    }

    /**
     * Index action method.
     *
     * Forwards to the emspayGateway Controller
     */

    public function indexAction()
    {
        return $this->forward('createOrder');
    }

    public function errorAction()
    {
        $this->View()->assign('help_message', $_SESSION['help_message']);
        $this->View()->assign('error_message', $_SESSION['error_message']);
        return true;
    }

    /**
     * Generate EMS Apple Pay.
     *
     * @param array
     * @return mixed
     */
    public function createOrderAction()
    {
        try {
            $basket = $this->helper->getBasket();
            $user = $this->helper->getUser();

            $preOrder = array_filter([
                'amount' => $this->helper->getAmountInCents($basket['sAmount']),                                // Amount in cents
                'currency' => $this->helper->getCurrencyName(),                                                 // Currency
                'merchant_order_id' => $this->helper->getOrderNumber(),                                         // Merchant Order Id
                'description' => $this->helper->getOrderDescription($this->helper->getOrderNumber()),           // Description
                'customer' => $this->helper->getCustomer($user),                                                // Customer information
                'payment_info' => [],                                                                           // Payment info
                'order_lines' => $this->helper->getOrderLines($basket, $user['additional']['payment']['name']),  // Order Lines
                'transactions' => $this->helper->getTransactions($this->payment_method),                        // Transactions Array
                'return_url' => $this->helper->getReturnUrl(self::CONTROLLER_NAME),                             // Return URL
                'webhook_url' => $this->helper->getWebhookUrl(self::CONTROLLER_NAME),                           // Webhook URL
                'extra' => ['plugin' => $this->helper->getPluginVersion()],                                     // Extra information
            ]);

            $ems_order = $this->ginger->createOrder($preOrder);
            $this->helper->clearEmsSession();

            if ($ems_order['status'] == 'error') {
                throw new Exception(current($ems_order['transactions'])['reason']);
            }
            if ($ems_order['status'] == 'cancelled') {
                throw new Exception("You order was cancelled, please try again later");
            }

            $_SESSION['emspa_payments_order_id'] = $ems_order['id'];

            if (isset($ems_order['order_url'])) {
                return $this->redirect($ems_order['order_url']);
            }
            if (current($ems_order['transactions'])['status'] == 'pending') {
                return $this->saveEmsOrder($ems_order['id'], $this->helper->getOrderToken(), $this->helper::EMS_TO_SHOPWARE_STATUSES[$ems_order['status']]);
            }
            return $this->Response()->setRedirect(current($ems_order['transactions'])['payment_url']);

        } catch (Exception $exception) {
            $logger = $this->get('corelogger');
            $logger->log('error', 'emspa_payments_' . $this->payment_method . " : " . $exception->getMessage());

            $_SESSION['ginger_warning_message'] = $_SESSION['ginger_warning_message'] ?: 'An error has occurred with the payment method. Please contact the store owner';
            return $this->redirect(['controller' => 'checkout', 'action' => 'confirm']);
        }
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
                $this->saveEmsOrder($ems_order['id'], $this->helper->getOrderToken(), $this->helper::EMS_TO_SHOPWARE_STATUSES[$ems_order['status']]);
                break;
            default:
                return $this->redirect(['controller' => 'checkout']);
        }
    }

    /**
     * Webhook
     * @return bool
     */
    public function webhookAction(): bool
    {
        $input = json_decode(file_get_contents("php://input"), true);
        if ($input['event'] != 'status_changed') {
            return false;
        }

        try {
            $ems_orderID = $input['order_id'];
            $service = $this->container->get("emspa_payments.service");
            $token = $service->createPaymentResponse($this->Request())->token;
            $emsOrder = $this->ginger->getOrder($ems_orderID);
        } catch (Exception $exception) {
            print_r("Error getting data from webhook" . $exception->getMessage());
            return false;
        }

        try {
            $this->savePaymentStatus($emsOrder['id'], $token, $this->helper::EMS_TO_SHOPWARE_STATUSES[$emsOrder['status']]);
            return true;
        } catch (Exception $exception) {
            print_r("Error saving order using webhook action" . $exception->getMessage());
            return false;
        }
    }

    /**
     * Save EMS order in ShopWare backend
     *
     * @param $id
     * @param $orderToken
     * @param $status
     * @return mixed
     */
    protected function saveEmsOrder($id, $orderToken, $status)
    {
        $this->saveOrder(
            $id,
            $orderToken,
            $status
        );
        return $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
    }
}