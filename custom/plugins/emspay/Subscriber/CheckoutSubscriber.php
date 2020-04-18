<?php

namespace emspay\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;

class CheckoutSubscriber implements SubscriberInterface
{
    protected $ems;
    protected $helper;
    /**
     * 0
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

        try{
        $ems_order = $this->ems->getOrder($ems_order_id);
        $ems_order['merchant_order_id'] = $shopware_order_id;
        $ems_order['description'] = (string)$this->helper->getOrderDescription($shopware_order_id);
            foreach ($ems_order['order_lines'] as $key => $order_line) {
                $order_line['amount'] = intval($order_line['amount']);
                $order_line['quantity'] = intval($order_line['quantity']);
                $order_line['vat_percentage'] = intval($order_line['vat_percentage']);
                $ems_order['order_lines'][$key] = $order_line;
            }
        $this->ems->updateOrder($ems_order['id'].'/',$ems_order);
        } catch (\Exception $exception){
            print_r($exception->getMessage());exit;
        }
        return true;
    }
}