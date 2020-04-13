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
//    public static function getSubscribedEvents() {
//
//        return [
//            'Shopware_Controllers_Frontend_Checkout::finishAction::after' => 'onCheckoutFinishUpdate'
//        ];
//    }

    /**
     * 1
     */
    public function onCheckoutFinishUpdate(\Enlight_Event_EventArgs $args){
        $payment_name = $args->getSubject()->View()->getAssign()['sUserData']['additional']['payment']['name'];
        if (in_array($payment_name,['emspay_klarnapaylater', 'emspay_afterpay'])) {
            $message = '<p>The invoice is sended to your email address</p>';
            $args->getSubject()->View()->assign(['FinishInfoConfirmationMail' => $message]);
            print_r($args->getSubject()->View()->getAssign());
        }
        return true;
    }
}