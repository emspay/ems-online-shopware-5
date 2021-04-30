<?php

namespace emspa_payments\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;

class AfterpaySubscriber implements SubscriberInterface
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
            'Shopware_Controllers_Frontend_Checkout::shippingPaymentAction::after' => 'displayBirthdaySelect',
            'Shopware_Controllers_Frontend_Checkout::saveShippingPaymentAction::before' => 'processBirthdaySelect',
        ];
    }

    /**
     * Update order on the EMS Side with orderId on FinishAction
     */
    public function displayBirthdaySelect(\Enlight_Event_EventArgs $args)
    {
        $this->helper = Shopware()->Container()->get('emspa_payments.helper');                                                                          //Create Helper
        $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspa_payments')); //Create EMS

        try {
            $this->ems->getIdealIssuers();
        } catch (\Exception $exception) {
            print_r($exception->getMessage());
            return false;
        }

        $subject = $args->getSubject();
        $payments = $subject->View()->getAssign()['sPayments'];

        foreach ($payments as $key => $payment) {
            if ($payment['name'] == 'emspa_payments_afterpay') {
                $payment['additionaldescription'] .= $this->addAfterPayBirthDay();
                $payments[$key] = $payment;
            }
        }
        $subject->View()->assign('sPayments', $payments);
    }

    /**
     * Get html content what include select tag with redirect to spec function what processing EMS iDEAL Issuer ID
     * @return string
     */
    private function addAfterPayBirthDay()
    {
        $content = "<div style='color: black;'>";
        $content .= "<form method='post'>";
        $content .= "<span>Please enter your date of birth in the format Year-Month-Day (YYYY-MM-DD)</span>" . "<br>";
        $content .= "<span id='emspay_payment_afterpay_incorrect_date' style='color: red; visibility: hidden;'>Please insert correct date</span><br>";
        $content .= "Birthday: <input type='text' name='emspa_payments_birthday' id='emspa_payments_birthday'>";
        $content .= "</form>";
        $content .= "</div>";

        $validation_script = "<script type='text/javascript'>document.getElementById('emspa_payments_birthday').addEventListener('change', function (){
    let is_date = this.value.match(/^(19|20)\d\d[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$/);
        if (!is_date) {
      this.style.borderColor = 'red';
      document.getElementById('emspay_payment_afterpay_incorrect_date').style.visibility = 'visible';
  } else {
      this.style.borderColor = 'green';
      document.getElementById('emspay_payment_afterpay_incorrect_date').style.visibility = 'hidden';
      
  }
});</script>";

        $content .= $validation_script;
        return $content;
    }

    /**
     * Save birth date from same field in payment select.
     * @param \Enlight_Event_EventArgs $args
     */
    public function processBirthdaySelect(\Enlight_Event_EventArgs $args)
    {
        if (!empty($_POST['emspa_payments_birthday'])) {
            $_SESSION['emspa_payments_birthday'] = $_POST['emspa_payments_birthday'];
        }
    }
}