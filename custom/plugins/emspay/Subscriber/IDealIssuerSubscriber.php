<?php

namespace emspay\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;

class IDealIssuerSubscriber implements SubscriberInterface
{
    protected $ems;
    protected $helper;
    /**
     * 0
     * @return array
     */
    public static function getSubscribedEvents() {

        return [
            'Shopware_Controllers_Frontend_Checkout::shippingPaymentAction::after' => 'displayIssuerSelect',
        ];
    }
    /**
     * Update order on the EMS Side with orderId on FinishAction
     */
    public function displayIssuerSelect(\Enlight_Event_EventArgs $args){
        $this->helper = Shopware()->Container()->get('emspay.helper');                                                                          //Create Helper
        $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay')); //Create EMS

        try {
            $issuers_array = $this->ems->getIdealIssuers();
        }catch (\Exception $exception) {
            print_r($exception->getMessage()); exit;
        }

        $subject = $args->getSubject();
        $payments = $subject->View()->getAssign()['sPayments'];

        foreach ($payments as $key => $payment){
            if ($payment['name'] == 'emspay_ideal'){
                $payment['additionaldescription'] .= $this->getIssuerIdSelector($issuers_array);
                $payments[$key] = $payment;
            }
        }
        $subject->View()->assign('sPayments',$payments);
    }

    /**
     * Get html content what include select tag with redirect to spec function what processing EMS iDEAL Issuer ID
     * @param $pm
     * @param $issuers_array
     * @return string
     */
    private function getIssuerIdSelector($issuers_array){
        $action_link = $this->helper->getProviderUrl('Gateway', 'processissuer');
        $content = '<span>Choose your bank:</span><br>';
        $content .= '<select name="issuer" onchange="location = this.value">';
        foreach ($issuers_array as $issuer) {
            if ($_SESSION['ems_issuer_id'] == null) {$_SESSION['ems_issuer_id']=$issuer['id'];}
            if (isset($_SESSION['ems_issuer_id']) && $_SESSION['ems_issuer_id'] == $issuer['id']) {$selected = 'selected';} else {$selected = null;}
            $content .= '<option '.$selected.' value="'.implode("",[$action_link,'?id=',$issuer['id']]).'">'.$issuer['name'].'</option>';
        }
        $content .= '</select>';
        return $content;
    }
}