<?php

namespace emspay\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;

class IDealIssuerSubscriber implements SubscriberInterface
{
    protected $ems;
    protected $helper;
    /**
     * Subscribe the event
     * @return array
     */
    public static function getSubscribedEvents() {

        return [
            'Shopware_Controllers_Frontend_Checkout::shippingPaymentAction::after' => 'displayIssuerSelect',
            'Shopware_Controllers_Frontend_Checkout::saveShippingPaymentAction::before' => 'processIssuerSelect',
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
        $content = '<span>Choose your bank:</span><br>';
        $content .= '<select name="issuer" id="emspay_issuer" name="emspay_issuer">';
        foreach ($issuers_array as $issuer) {
            if ($_SESSION['ems_issuer_id'] == null) {$_SESSION['ems_issuer_id']=$issuer['id'];}
            if (isset($_SESSION['ems_issuer_id']) && $_SESSION['ems_issuer_id'] == $issuer['id']) {$selected = 'selected';} else {$selected = null;}
            $content .= '<option '.$selected.' value="'.$issuer['id'].'">'.$issuer['name'].'</option>';
        }
        $content .= '</select>';
        return $content;
    }

    /**
     * Method for save iDEAL issuer ID
     */
    public function processIssuerSelect(){
        if (!empty($_POST['issuer'])) {
            $_SESSION['ems_issuer_id'] = $_POST['issuer'];
        }
    }
}