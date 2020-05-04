<?php

namespace emspay\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;

class AfterpaySubscriber implements SubscriberInterface
{
    protected $ems;
    protected $helper;
    /**
     * 0
     * @return array
     */
    public static function getSubscribedEvents() {

        return [
            'Shopware_Controllers_Frontend_Checkout::shippingPaymentAction::after' => 'displayBirthdaySelect',
            'Shopware_Controllers_Frontend_Checkout::saveShippingPaymentAction::before' => 'processBirthdaySelect',
        ];
    }
    /**
     * Update order on the EMS Side with orderId on FinishAction
     */
    public function displayBirthdaySelect(\Enlight_Event_EventArgs $args){
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
            if ($payment['name'] == 'emspay_afterpay'){
                $payment['additionaldescription'] .= $this->addAfterPayBirthDay();
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
    private function addAfterPayBirthDay(){
        $content = "<div style='color: black;'>";
        $content .= "<form method='post'>";
        $content .= "<span>Please enter your date of birth in the format Year/Month/Day (****/**/**)</span>"."<br>";
        $content .= "Birthday: <input type='text' name='emspay_birthday' id='emspay_birthday'>";
        $content .= "</form>";
        $content .= "</div>";
        return $content;
    }

    public function processBirthdaySelect(\Enlight_Event_EventArgs $args){
        if (!empty($_POST['emspay_birthday'])) {
            $_SESSION['emspay_birthday'] = $_POST['emspay_birthday'];
        }
    }
}