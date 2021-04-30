<?php

namespace emspa_payments\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use emspa_payments\Components\emspay\Helper;
use Enlight\Event\SubscriberInterface;

class ProcessPaymentDisplaySubscriber implements SubscriberInterface
{
    protected $ems;
    /**
     * @var Helper
     */
    protected $helper;
    private $config;

    /**
     * Subscribe the event
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Frontend_Checkout::shippingPaymentAction::after' => 'processPaymentDisplay',
            'Shopware_Controllers_Frontend_Checkout::saveShippingPaymentAction::before' => 'processIssuerSelect',
        ];
    }

    /**
     * Check IP addresses and hide unavailable payment methods
     * @param \Enlight_Event_EventArgs $args
     */
    public function processPaymentDisplay(\Enlight_Event_EventArgs $args)
    {
        try {
            $this->helper = Shopware()->Container()->get('emspa_payments.helper');                                                                          //create new helper object
            $this->config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspa_payments');                        //get the library config
            $this->ems = $this->helper->getClient($this->config);                                                                                           //create new ginger client object
            $allowed_payments = [];
            $country = $args->getSubject()->getUserData()['additional']['country']['countryiso'];

            foreach ($args->getSubject()->View()->getAssign()['sPayments'] as $key => $payment) {
                $name = explode('emspa_payments_', $payment['name'])[1];
                try {
                    if ($name == 'ideal') {
                        try {
                            $issuers = $this->ems->getIdealIssuers();
                            $payment['additionaldescription'] .= $this->getIssuerIdSelector($issuers);
                        } catch (\Exception $exception) {
                            $payment['additionaldescription'] .= '';
                        }
                    }

                    if ($this->ipAddressValidation($name) && $this->countryValidation($name, $country)) {
                        $allowed_payments[$key] = $payment;
                    }
                } catch (\Exception $exception) {
                }
            }
            $args->getSubject()->View()->assign('sPayments', $allowed_payments);
        } catch (\Exception $exception) {
            $_SESSION['error_message'] = $exception->getMessage();
            return $args->getSubject()->redirect(['controller' => 'emspayGateway', 'action' => 'error']);
        }
    }

    /**
     * Get html content what include select tag with redirect to spec function what processing EMS iDEAL Issuer ID
     * @param $issuers_array
     * @return string
     */
    private function getIssuerIdSelector($issuers_array): string
    {
        $content = '<span>Choose your bank:</span><br>';
        $content .= '<select name="issuer" id="emspay_issuer" name="emspay_issuer">';
        foreach ($issuers_array as $issuer) {
            if ($_SESSION['ems_issuer_id'] == null) {
                $_SESSION['ems_issuer_id'] = $issuer['id'];
            }
            if (isset($_SESSION['ems_issuer_id']) && $_SESSION['ems_issuer_id'] == $issuer['id']) {
                $selected = 'selected';
            } else {
                $selected = null;
            }
            $content .= '<option ' . $selected . ' value="' . $issuer['id'] . '">' . $issuer['name'] . '</option>';
        }
        $content .= '</select>';
        return $content;
    }

    /**
     * Method for save iDEAL issuer ID
     */
    public function processIssuerSelect()
    {
        if (!empty($_POST['issuer'])) {
            $_SESSION['ems_issuer_id'] = $_POST['issuer'];
        }
    }

    /**
     * Check that the user's country matches the country for which Afterpay payment method is available
     * @param $method
     * @param $country
     * @return bool
     */

    private function countryValidation($method, $country)
    {
        $countries_string = $this->config[implode('_', ['emsonline', $method, 'countries'])] ?? "";
        $countries_list = array_map('trim', explode(",", $countries_string));

        return $countries_string != "" ? in_array($country, $countries_list) : true;
    }

    /**
     * Check current IP address with Test IP Addresses
     * @param $method
     * @return bool
     */
    private function ipAddressValidation($method)
    {
        $test_ip = $this->config[implode('_', ['emsonline_test_ip', $method])] ?? "";
        $ip_list = array_map('trim', explode(",", $test_ip));

        return $test_ip != "" ? in_array($this->helper->getIpOfTheServer(), $ip_list) : true;
    }
}