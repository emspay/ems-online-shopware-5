<?php

namespace emspay\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;

class PaymentKeeperSubscriber implements SubscriberInterface
{
    protected $ems;
    protected $helper;
    protected $config;
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
     * @param \Enlight_Event_EventArgs $args
     */
    public function checkForDisplay(\Enlight_Event_EventArgs $args)
    {
        $allowed_payments = array();
        $this->helper = Shopware()->Container()->get('emspay.helper');                                                                          //Create Helper
        $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay')); //Create EMS
        $this->config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay');
        $country = $args->getSubject()->getUserData()['additional']['country']['countryiso'];

        foreach ($args->getSubject()->View()->getAssign()['sPayments'] as $key => $payment) {
           $name = explode('emspay_',$payment['name'])[1];
            if ($this->ipAddressValidation($name) && $this->countryValidation($name,$country)) {
                $allowed_payments[$key] = $payment;
            }

        }
        $args->getSubject()->View()->assign('sPayments',$allowed_payments);
    }

    /**
     * Check that the user's country matches the country for which Afterpay payment method is available
     * @param $method
     * @param $country
     * @return bool
     */

    private function countryValidation($method,$country){
        $countries_string = $this->config[implode('_',['emsonline',$method,'countries'])];
        if ($countries_string == "") return true;
        $countries_list = array_map('trim', explode(",", $countries_string));

        return in_array($country, $countries_list);
    }

    /**
     * Check current IP address with Test IP Addresses
     * @param $method
     * @return bool
     */
    private function ipAddressValidation($method){
        $test_ip = $this->config[implode('_',['emsonline_test_ip',$method])];
        if ($test_ip == "") return true;
        $ip_list = array_map('trim', explode(",", $test_ip));

        return in_array($this->helper->getIpOfTheServer(), $ip_list);
    }
}