<?php

namespace emspay\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;

class IPBlockSubscriber implements SubscriberInterface
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
            'Shopware_Controllers_Frontend_Checkout::shippingPaymentAction::after' => 'checkForDisplay',
        ];
    }

    /**
     * Check IP addresses and hide unavailable payment methods
     */
    public function checkForDisplay(\Enlight_Event_EventArgs $args)
    {
        $allowed_payments = array();
        $this->helper = Shopware()->Container()->get('emspay.helper');                                                                          //Create Helper
        $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay')); //Create EMS

        foreach ($args->getSubject()->View()->getAssign()['sPayments'] as $key => $payment) {
           $name = explode('emspay_',$payment['name'])[1];
            if ($this->ipAddressValidation($name)) {
                $allowed_payments[$key] = $payment;
            }

        }
        $args->getSubject()->View()->assign('sPayments',$allowed_payments);
    }

    /**
     * Check current IP address with Test IP Addresses
     * @return bool
     */
    private function ipAddressValidation($method){
        $config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay');
        $test_ip = $config[implode('_',['emsonline_test_ip',$method])];
        if ($test_ip == "") return true;
        $ip_list = array_map('trim', explode(",", $test_ip));

        return in_array($this->helper->getIpOfTheServer(), $ip_list);
    }
}