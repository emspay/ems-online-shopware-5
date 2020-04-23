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
            'Shopware_Controllers_Frontend_Checkout::confirmAction::after' => 'processIssuerSelect'
        ];
    }

    public function processIssuerSelect(\Enlight_Event_EventArgs $args){
        $this->helper = Shopware()->Container()->get('emspay.helper');                                                                          //Create Helper
        $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay')); //Create EMS

        $subject = $args->getSubject();
        $assign = $subject->View()->getAssign();
      //  print_r($assign['sPayment']);exit;
    }

    /**
     * Update order on the EMS Side with orderId on FinishAction
     */
    public function displayIssuerSelect(\Enlight_Event_EventArgs $args){
        $this->helper = Shopware()->Container()->get('emspay.helper');                                                                          //Create Helper
        $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay')); //Create EMS

        $issuers_array = $this->ems->getIdealIssuers();
        $subject = $args->getSubject();
        $payments = $subject->View()->getAssign()['sPayments'];
        foreach ($payments as $key => $payment){
            if ($payment['name'] == 'emspay_ideal'){
                $payment['additionaldescription'] .= $this->getIssuerIdSelector($payment['name'],$issuers_array);
                $payments[$key] = $payment;
            }
        }
       // print_r($payments);exit;
        $subject->View()->assign('sPayments',$payments);
    }
    private function getIssuerIdSelector($pm,$issuers_array){
        //print_r($_GET);exit;
        $action_link = $this->helper->getProviderUrl('EmsPayIDeal', 'processissuer');
        $content = '<span>Choose your bank:</span><br>';
        $content .= '<select name="issuer" id="'.$pm.'" required="required" onchange="location = this.value">';
        foreach ($issuers_array as $issuer) {
            if ($_SESSION['ems_issuer_id'] == null) {$_SESSION['ems_issuer_id']=$issuer['id'];}
            if (isset($_SESSION['ems_issuer_id']) && $_SESSION['ems_issuer_id'] == $issuer['id']) {$selected = 'selected';} else {$selected = null;}
            $content .= '<option '.$selected.' value="'.implode("",[$action_link,'?id=',$issuer['id']]).'">'.$issuer['name'].'</option>';
        }
        $content .= '</select>';
        return $content;
    }
}