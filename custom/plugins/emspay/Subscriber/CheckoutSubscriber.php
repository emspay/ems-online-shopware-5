<?php

namespace emspay\Subscriber;

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
    public static function getSubscribedEvents() {

        return [
            'Shopware_Controllers_Frontend_Checkout::finishAction::after' => 'onCheckoutFinishUpdate'
        ];
    }

    /**
     * Update order on the EMS Side with orderId on FinishAction
     */
    public function onCheckoutFinishUpdate(\Enlight_Event_EventArgs $args){
        //Check if selected payment method is EMS Online
        $payment = explode('_', $args->getSubject()->View()->getAssign()['sPayment']['name']);
        $provider = $payment[0];
        $method = $payment[1];

        if ($provider != 'emspay'){
            return null;
        }

        try{
        $this->helper = Shopware()->Container()->get('emspay.helper');                                                                          //Create Helper
        $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay')); //Create EMS

        $order_number = ($args->getSubject()->View()->getAssign()['sOrderNumber']);
        $orders = (Shopware()->Modules()->Admin()->sGetOpenOrderData())['orderData'];
        $shopware_order_id = null;
        $ems_order_id = null;
        foreach ($orders as $order){
            if ($order['ordernumber'] == $order_number) {
                $ems_order_id = $order['transactionID'];
                $shopware_order_id = $order['id'];
                break;
            }
        }


        $ems_order = $this->ems->getOrder($ems_order_id);

        if ($method == 'banktransfer') {
            $view = $args->getSubject()->View();
            $this->showIbanInformation($view,current($ems_order['transactions'])['payment_method_details']);
        }

        $ems_order['merchant_order_id'] = $shopware_order_id;
        $ems_order['description'] = (string)$this->helper->getOrderDescription($shopware_order_id);
        $this->ems->updateOrder($ems_order['id'],$ems_order);
        } catch (\Exception $exception){
            $_SESSION['error_message'] = $exception->getMessage();
            return $args->getSubject()->redirect(['controller' => 'Gateway', 'action' => 'error']);
        }
        return true;
    }

    private function showIbanInformation($view,$details){
    $view->addTemplateDir(__DIR__.'/../Resources/views/');
    $view->extendsTemplate(
            'frontend/checkout/finish.tpl'
        );
    $view->assign('emspayIbanInformation',$details);
    }
}