<?php

use emspayapplepay\Components\ApplePayPayment\PaymentResponse;
use emspayapplepay\Components\ApplePayPayment\ApplePayPaymentService;
require_once (realpath("engine/Library/emspay/emshelper.php"));

class Shopware_Controllers_Frontend_ApplePayPayment extends Shopware_Controllers_Frontend_Payment
{
    const PAYMENTSTATUSPAID = 12;

    /**
     * @var EmsHelper
     */
    public $emsHelper;

    /**
     * @var \Ginger\Ginger
     */
    private $ems;

    public function preDispatch()
    {
        $plugin = $this->get('kernel')->getPlugins()['emspayapplepay'];

        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views');
    }

    /**
     * Index action method.
     *
     * Forwards to the correct action.
     */

    public function indexAction()
    {
        /**
         * Check if one of the payment methods is selected. Else return to default controller.
         */
        switch ($this->getPaymentShortName()) {
            case 'ApplePayPayment':
                return $this->redirect(['action' => 'gateway', 'forceSecure' => true]);
            default:
                return null;
        }
    }

    /**
     * Gateway action method.
     *
     * Collects the payment information and transmit it to the payment provider.
     */
    public function gatewayAction()
    {
        $config = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName($this->getPaymentShortName(),Shopware()->Shop());
        $this->emsHelper = new EmsHelper($this->getClassName());
        $this->ems = $this->emsHelper->getClient($config);

        $orderBasket = array_merge($this->getBasket(),
            ['currency' => $this->getCurrencyShortName()],
            ['shop_name' => Shopware()->Shop()->getName()],
            $this->getUser(),
            ['locale' => Shopware()->Shop()->getLocale()->getLocale()]);

        print_r($this->emsHelper->getOrderData($orderBasket));exit;
    }


    /**
     * Direct action method.
     *
     * Collects the payment information and transmits it to the payment provider.
     */
    public function directAction()
    {
        $providerUrl = $this->getProviderUrl();
       $this->redirect($providerUrl . $this->getUrlParameters());
       exit();
    }

    /**
     * Return action method
     *
     * Reads the transactionResult and represents it for the customer.
     */
    public function returnAction()
    {
        /** @var ApplePayPaymentService $service */
        $service = $this->container->get('ApplePayPayment.applepay_payment_service');
        $user = $this->getUser();
        $billing = $user['billingaddress'];
        /** @var PaymentResponse $response */
        $response = $service->createPaymentResponse($this->Request());
        $token = $service->createPaymentToken($this->getAmount(), $billing['customernumber']);

        if (!$service->isValidToken($response, $token)) {
            $this->forward('cancel');

            return;
        }

        switch ($response->status) {
            case 'accepted':
                $this->saveOrder(
                    $response->transactionId,
                    $response->token,
                    self::PAYMENTSTATUSPAID
                );
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            default:
                $this->forward('cancel');
                break;
        }
    }

    /**
     * Cancel action method
     */
    public function cancelAction()
    {
        echo 123;
    }

    /**
     * Creates the url parameters
     */
    private function getUrlParameters()
    {
        /** @var ApplePayPaymentService $service */
        $service = $this->container->get('ApplePayPayment.applepay_payment_service');
        $router = $this->Front()->Router();
        $user = $this->getUser();
        $billing = $user['billingaddress'];

        $parameter = [
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrencyShortName(),
            'firstName' => $billing['firstname'],
            'lastName' => $billing['lastname'],
            'returnUrl' => $router->assemble(['action' => 'return', 'forceSecure' => true]),
            'cancelUrl' => $router->assemble(['action' => 'cancel', 'forceSecure' => true]),
            'token' => $service->createPaymentToken($this->getAmount(), $billing['customernumber'])
        ];

        return '?' . http_build_query($parameter);
    }

    /**
     * Returns the URL of the payment provider. This has to be replaced with the real payment provider URL
     *
     * @return string
     */
    protected function getProviderUrl()
    {
        return $this->Front()->Router()->assemble(['controller' => '', 'action' => 'pay']);
    }
}
