<?php

namespace emspay\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;
use emspay\Components\Emspay\Helper;

class StatusSubscriber implements SubscriberInterface
{
    protected $ems;
    protected $helper;
    /**
     * 0
     * @return array
     */
    public static function getSubscribedEvents() {

        return [
            'Shopware_Controllers_Backend_Order::saveAction::before' => 'onStatusOrderUpdate'
        ];
    }

    /**
     * 1
     */
    public function onStatusOrderUpdate(\Enlight_Event_EventArgs $args){
        $request = $args->getSubject()->Request()->getParams();
        if ($request['status'] == 7 && in_array($request['payment'][0]['name'],['emspaypaynow','emspay_klarnapaylater','emspay_afterpay']))
        {
            $this->helper = Shopware()->Container()->get('emspay.helper'); //Create Helper
            $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay')); //Create EMS

            $orderId = $request['transactionId'];
            $emsOrder = $this->ems->getOrder($orderId);
            $transactionId = !empty(current($emsOrder['transactions'])) ? current($emsOrder['transactions'])['id'] : null;
//             print_r($transactionId.'+++'.$orderId);exit;
            print_r($this->ems->captureOrderTransaction($orderId,$transactionId));
            exit;
        }
        return true;
    }
}