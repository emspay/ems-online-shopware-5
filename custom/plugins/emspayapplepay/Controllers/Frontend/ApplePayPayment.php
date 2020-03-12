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

        $this->emsHelper = new EmsHelper($this->getClassName());

        $config = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName($this->getPaymentShortName(),Shopware()->Shop());

        $this->ems = $this->emsHelper->getClient($config);
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
                return $this->redirect(['action' => 'direct', 'forceSecure' => true]);
        }
    }

    /**
     * Gateway action method.
     *
     * Collects the payment information and transmit it to the payment provider.
     */
    public function gatewayAction()
    {
        $providerUrl = $this->getProviderUrl();
        $this->View()->assign('gatewayUrl', $providerUrl . $this->getUrlParameters());

    }

    /**
     * Generate EMS Apple Pay.
     *
     * @param array
     * @return array
     */
    protected function createOrder(array $orderData)
    {
        return $this->ems->createOrder([
            'amount' => $orderData['amount'],                                // Amount in cents
            'currency' => $orderData['currency'],                            // Currency
            'description' => $orderData['description'],                      // Description
            'merchant_order_id' => (string) $orderData['merchant_order_id'], // Merchant Order Id
            'return_url' => $orderData['return_url'],                        // Return URL
            'customer' => $orderData['customer'],                            // Customer information
            'extra' => $orderData['plugin_version'],                         // Extra information
            'webhook_url' => $orderData['webhook_url'],                      // Webhook URL
            'transactions' => [
                [
                    'payment_method' => "apple-pay"
                ]
            ]
        ]);
    }

    /**
     * Direct action method.
     *
     * Collects the payment information and transmits it to the payment provider.
     */
    public function directAction()
    {
        $emsOrderData = $this->emsHelper->getOrderData($this->completeOrderData());
        $emsOrder = $this->createOrder($emsOrderData);

        if (isset($emsOrder['transactions'][0]['payment_url'])){
        $this->redirect($emsOrder['transactions'][0]['payment_url']);
        } else {
            print_r("Error processing order");
        }
    }

    /**
     * Return action method
     *
     * Reads the transactionResult and represents it for the customer.
     */
    public function returnAction()
    {
        print_r(123);exit;
        /** @var ApplePayPaymentService $service */
        $service = $this->container->get('emspayapplepay.applepay_payment_service');
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

    }

    /**
     * Creates the url parameters
     */
    private function getUrlParameters()
    {
        /** @var ApplePayPaymentService $service */
        $service = $this->container->get('emspayapplepay.applepay_payment_service');
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
    protected function getProviderUrl($controller = '',$action = 'pay')
    {
        return $this->Front()->Router()->assemble(['controller' => $controller, 'action' => $action]);
    }

    protected function completeOrderData(){
        $webhook = $this->getProviderUrl(). $this->getUrlParameters();
        $return_url = $this->getProviderUrl('ApplePayPayment','cancel');
        return array_merge($this->getBasket(),
            ['currency' => $this->getCurrencyShortName()],
            ['shop_name' => Shopware()->Shop()->getName()],
            $this->getUser(),
            ['locale' => Shopware()->Shop()->getLocale()->getLocale()],
            ['webhook_url' => $webhook],
            ['return_url' => $return_url]);
    }
}
