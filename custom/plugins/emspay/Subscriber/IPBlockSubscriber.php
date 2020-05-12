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
        $this->helper = Shopware()->Container()->get('emspay.helper');                                                                          //Create Helper
        $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay')); //Create EMS

        foreach ($args->getSubject()->View()->getAssign()['sPayments'] as $payment) {
            switch ($payment['name']) {
                case 'emspay_klarnapaylater' :
                $klarna_id = $payment['id'];
                break;
                case 'emspay_afterpay' :
                $afterpay_id = $payment['id'];
            }
        }
        if (!empty($klarna_id) && !$this->ipAddressValidation('klarna')) {
            $this->cleanUp($args,$klarna_id);
        }
        if (!empty($afterpay_id) && !$this->ipAddressValidation('afterpay')) {
            $this->cleanUp($args,$afterpay_id);
        }
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

    /**
     * Clean Up View for unavailable payment methods
     * @param $args
     * @param $payment_id
     */
    private function cleanUp($args,$payment_id){
        
        $view = $args->getSubject()->View();
        $assigned = $view->getAssign();

        //Clean up list of payment methods
        unset($assigned['sPayments'][$payment_id]);
        $view->assign('sPayments', $assigned['sPayments']);

        //Clean up user-selected payment method
        if ($assigned['sUserData']['additional']['payment']['id'] == $payment_id) {
            unset($assigned['sUserData']['additional']['payment']);
            $assigned['sUserData']['additional']['user']['paymentID'] = null;
        }
        $view->assign('sUserData', $assigned['sUserData']);

        //Clean up Form Data
        if ($assigned['sFormData']['payment'] == $payment_id) {
            $assigned['sFormData']['payment'] = null;
        }
        $view->assign('sFormData', $assigned['sFormData']);
    }
}