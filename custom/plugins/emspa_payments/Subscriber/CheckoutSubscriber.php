<?php

namespace emspa_payments\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;

class CheckoutSubscriber implements SubscriberInterface
{
    protected $ems;
    protected $helper;

    /**
     * Subscribe the event
     * @return array
     */
    public static function getSubscribedEvents()
    {

        return [
            'Shopware_Controllers_Frontend_Checkout::finishAction::after' => 'onCheckoutFinishUpdate',
            'Shopware_Controllers_Frontend_Checkout::confirmAction::after' => 'onCheckoutReturnNotice'
        ];
    }

    public function onCheckoutReturnNotice(\Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();

        if (isset($_SESSION['ginger_warning_message'])) {
            $this->addWarningMessage($view, $_SESSION['ginger_warning_message']);
            unset($_SESSION['ginger_warning_message']);
            return 0;
        }

        if (!isset($_SESSION['emspa_payments_order_id'])) {
            return 0;
        }

        $this->helper = Shopware()->Container()->get('emspa_payments.helper');                                                                          //Create Helper
        $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspa_payments')); //Create EMS
        $ginger_order = $this->ems->getOrder($_SESSION['emspa_payments_order_id']);
        $transaction = current($ginger_order['transactions']);
        $event = end($transaction['events']);
        unset($_SESSION['emspa_payments_order_id']);


        switch ($event['event']) {
            case 'completed' :
                return 0;
            case 'new' :
                $warning_message = 'The payment was cancelled on the payment page';
                break;
            case 'cancelled' :
                $warning_message = 'The payment was canceled by the payment provider';
                break;
            case 'error' :
                $logger = Shopware()->Container()->get('corelogger');
                $logger->log('error', "emspa_payments_" . $transaction['payment_method'] . " : " . $transaction['reason']);
                $warning_message = 'An error has occurred with the payment method. Please contact the store owner';
                break;
            default :
                $warning_message = 'Undefined, but you still can pay using EMS Online';
        }
        $this->addWarningMessage($view, $warning_message);
        return 0;
    }

    public function addWarningMessage($view, $message)
    {
        $view->addTemplateDir(__DIR__ . '/../Resources/views/');
        $view->extendsTemplate(
            'frontend/checkout/confirm.tpl'
        );
        $view->assign('warning_message', $message);
    }

    /**
     * Update order on the EMS Side with orderId on FinishAction
     */
    public function onCheckoutFinishUpdate(\Enlight_Event_EventArgs $args)
    {
        //Check if selected payment method is EMS Online
        $payment = explode('_', $args->getSubject()->View()->getAssign()['sPayment']['name']);
        $provider = implode('_', [$payment[0], $payment[1]]);
        $method = $payment[2];
        if ($provider != 'emspa_payments') {
            return null;
        }

        try {
            $this->helper = Shopware()->Container()->get('emspa_payments.helper');                                                                          //Create Helper
            $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspa_payments')); //Create EMS

            $order_number = ($args->getSubject()->View()->getAssign()['sOrderNumber']);
            $orders = (Shopware()->Modules()->Admin()->sGetOpenOrderData())['orderData'];
            $shopware_order_id = null;
            $ems_order_id = null;
            foreach ($orders as $order) {
                if ($order['ordernumber'] == $order_number) {
                    $ems_order_id = $order['transactionID'];
                    $shopware_order_id = $order['id'];
                    break;
                }
            }


            $ems_order = $this->ems->getOrder($ems_order_id);

            if ($method == 'banktransfer') {
                $view = $args->getSubject()->View();
                $this->showIbanInformation($view, current($ems_order['transactions'])['payment_method_details']);
            }

            $ems_order['merchant_order_id'] = $shopware_order_id;
            $ems_order['description'] = (string)$this->helper->getOrderDescription($shopware_order_id);
            $this->ems->updateOrder($ems_order['id'], $ems_order);
        } catch (\Exception $exception) {
            $_SESSION['error_message'] = $exception->getMessage();
            return $args->getSubject()->redirect(['controller' => 'emspayGateway', 'action' => 'error']);
        }
        return true;
    }

    private function showIbanInformation($view, $details)
    {
        $view->addTemplateDir(__DIR__ . '/../Resources/views/');
        $view->extendsTemplate(
            'frontend/checkout/finish.tpl'
        );
        $view->assign('emspayIbanInformation', $details);
    }
}