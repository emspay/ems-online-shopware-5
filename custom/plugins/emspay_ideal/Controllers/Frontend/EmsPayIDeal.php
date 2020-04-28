<?php
class Shopware_Controllers_Frontend_EmsPayIDeal extends Shopware_Controllers_Frontend_Payment
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
