<?php
class Shopware_Controllers_Frontend_EmsPayKlarnaPayLater extends Shopware_Controllers_Frontend_Payment
{
    /**
     * Index action method.
     *
     * Forwards to the Gateway Controller
     */

    public function indexAction()
    {
        return  $this->forward('createOrder','Gateway');
    }
}
