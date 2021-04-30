<?php

namespace emspa_payments\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;

class StatusSubscriber implements SubscriberInterface
{
    protected $ems;
    protected $helper;
    /**
     * Subscribe the event
     * @return array
     */
    public static function getSubscribedEvents() {

        return [
            'Shopware_Controllers_Backend_Order::saveAction::before' => 'onStatusOrderUpdate'
        ];
    }

    /**
     * Capture order on EMS side if status changed to complete deliver
     */
    public function onStatusOrderUpdate(\Enlight_Event_EventArgs $args){
        $request = $args->getSubject()->Request()->getParams();
        if ($request['status'] == 7 && $request['status']!=current($request['orderStatus'])['id'] && in_array(current($request['payment'])['name'],['emspa_payments_klarnapaylater','emspa_payments_afterpay']))
        {
            $this->helper = Shopware()->Container()->get('emspa_payments.helper');                                                                          //Create Helper
            $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspa_payments')); //Create EMS

            $orderId = $request['transactionId'];
            $emsOrder = $this->ems->getOrder($orderId);
            $transactionId = !empty(current($emsOrder['transactions'])) ? current($emsOrder['transactions'])['id'] : null;
            $this->ems->captureOrderTransaction($orderId,$transactionId);
        }
        return true;
    }
}