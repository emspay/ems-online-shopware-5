<?php

namespace emspay;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Payment\Payment;

class emspay extends Plugin
{
    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        $options = [
            'name' => 'emspay',
            'description' => 'Library for EMS Online Payments',
            'active' => 0,
            'position' => 1,
            'additionalDescription' => 'Don\'t set this payment by active'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);
        $options = [
            'name' => 'emspay_paynow',
            'description' => 'EMS Online Pay Now',
            'action' => 'Gateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspay/Payment_description/emspay_paynow.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);
        $options = [
            'name' => 'emspay_applepay',
            'description' => 'EMS Online Apple Pay',
            'action' => 'Gateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspay/Payment_description/emspay_applepay.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);
        $options = [
            'name' => 'emspay_ideal',
            'description' => 'EMS Online iDEAL',
            'action' => 'Gateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspay/Payment_description/emspay_ideal.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);
        $options = [
            'name' => 'emspay_klarnapaylater',
            'description' => 'EMS Online Klarna Pay Later',
            'action' => 'Gateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspay/Payment_description/emspay_klarnapaylater.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);
        $options = [
            'name' => 'emspay_klarnapaynow',
            'description' => 'EMS Online Klarna Pay Now',
            'action' => 'Gateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspay/Payment_description/emspay_klarnapaynow.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
    }

    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), true);
    }

    /**
     * @param Payment[] $payments
     * @param $active bool
     */
    private function setActiveFlag($payments, $active)
    {
        $em = $this->container->get('models');

        foreach ($payments as $payment) {
            $payment->setActive($active);
        }
        $em->flush();
    }
}
