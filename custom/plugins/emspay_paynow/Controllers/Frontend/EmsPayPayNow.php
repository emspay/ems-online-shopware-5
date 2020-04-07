<?php

class Shopware_Controllers_Frontend_EmsPayPayNow extends Shopware_Controllers_Frontend_Payment
{
    /**
     * @var \emspay\Components\Emspay\Helper
     */
    public $emsHelper;

    /**
     * @var \Ginger\Ginger
     */
    private $ems;

    /**
     * @var string
     */
    CONST EMS_PAY_PLUGIN_NAME = 'emspay_paynow';

    public function preDispatch()
    {
        $plugin = $this->get('kernel')->getPlugins()[self::EMS_PAY_PLUGIN_NAME];

        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views');

        $this->emsHelper = $this->container->get('emspay.helper');

        $this->ems = $this->emsHelper->getClient($this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('emspay',Shopware()->Shop()));
    }

    /**
     * Index action method.
     *
     * Forwards to the correct action.
     */

    public function indexAction()
    {
        return $this->redirect(['action' => 'direct', 'forceSecure' => true]);
    }

    /**
     * Direct action method.
     *
     * Create the order and transmits it to the payment provider.
     */
    public function directAction()
    {
        $emsOrderData = $this->emsHelper->getOrderData($this->completeOrderData());
        try{
        $emsOrder = $this->emsHelper->createOrder($emsOrderData,$this->ems);
        } catch (Exception $exception) {
            print_r($exception->getMessage());exit;
        }
        if ($emsOrder['status'] == 'error') {
            print_r("Error while creating your EMS order , please try again later"); exit;
        }

        if (isset($emsOrder['order_url'])){
            $this->redirect($emsOrder['order_url']);
        } else {
            print_r("Error while redirecting to the EMS payment page, please try again later"); exit;
        }
    }

    /**
     * Return action method
     *
     * Reads the transactionResult and represents it for the customer.
     */
    public function returnAction()
    {
        $service = $this->container->get("emspay.service");
        $user = $this->getUser();
        $billing = $user['billingaddress'];
        $token = $service->createPaymentToken($this->getAmount(), $billing['customernumber']);

        if (!isset($this->emsHelper)) {
            $this->emsHelper = new EmsHelper($this->getClassName());
        }
        $ems_order = $this->ems->getOrder($_GET['order_id']);
        switch ($ems_order['status']) {
            case 'completed':
                 $this->saveOrder(
                    $ems_order['id'],
                    $token,
                    $this->emsHelper::EMS_TO_SHOPWARE_STATUSES[$ems_order['status']]
                );
                return $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            case 'cancelled':
                return $this->forward('cancel');
                break;
            default:
                return $this->redirect(['controller' => 'checkout']);
        }
    }

    /**
     * Creates the url parameters
     */
    private function getUrlParameters()
    {
        $service = $this->container->get('emspay.service');
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

    /**
     * Former array with all required data using for creating EMS Order
     * @return array
     */
    protected function completeOrderData(){
        $webhook = $this->getProviderUrl('EmsPayPayNow','webhook'). $this->getUrlParameters();
        $return_url = $this->getProviderUrl('EmsPayPayNow','return');
        return array_merge($this->getBasket(),
            ['currency' => $this->getCurrencyShortName()],
            ['shop_name' => Shopware()->Shop()->getName()],
            $this->getUser(),
            ['locale' => Shopware()->Shop()->getLocale()->getLocale()],
            ['webhook_url' => $webhook],
            ['return_url' => $return_url]);
    }
}
