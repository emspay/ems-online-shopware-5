<?php

namespace emspa_payments;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Payment\Payment;

class emspa_payments extends Plugin
{
    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        /**
         *  Install library
         */
        $options = [
            'name' => 'emspa_payments',
            'description' => 'Library for EMS Online Payments',
            'active' => 0,
            'position' => 1,
            'additionalDescription' => 'Don\'t set this payment by active'
        ];

        /**
         *  Install Pay Now Payment
         */
        $installer->createOrUpdate($context->getPlugin(), $options);
        $options = [
            'name' => 'emspa_payments_paynow',
            'description' => 'EMS Online Pay Now',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_paynow.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];

        /**
         *  Install Apple Pay Payment
         */
        $installer->createOrUpdate($context->getPlugin(), $options);
        $options = [
            'name' => 'emspa_payments_applepay',
            'description' => 'EMS Online Apple Pay',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_applepay.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];

        /**
         *  Install iDEAL Payment
         */
        $installer->createOrUpdate($context->getPlugin(), $options);
        $options = [
            'name' => 'emspa_payments_ideal',
            'description' => 'EMS Online iDEAL',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_ideal.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];

        /**
         *  Install Klarna Pay Later Payment
         */
        $installer->createOrUpdate($context->getPlugin(), $options);
        $options = [
            'name' => 'emspa_payments_klarnapaylater',
            'description' => 'EMS Online Klarna Pay Later',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_klarnapaylater.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];

        /**
         *  Install Klarna Pay Now Payment
         */
        $installer->createOrUpdate($context->getPlugin(), $options);
        $options = [
            'name' => 'emspa_payments_klarnapaynow',
            'description' => 'EMS Online Klarna Pay Now',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_klarnapaynow.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];

        /**
         *  Install AfterPay Payment
         */
        $installer->createOrUpdate($context->getPlugin(), $options);
        $options = [
            'name' => 'emspa_payments_afterpay',
            'description' => 'EMS Online AfterPay',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_afterpay.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);

        /**
         *  Install American Express Payment
         */
        $installer->createOrUpdate($context->getPlugin(), $options);
        $options = [
            'name' => 'emspa_payments_amex',
            'description' => 'EMS Online American Express',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_amex.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);

        /**
         *  Install Bancontact Payment
         */
        $options = [
            'name' => 'emspa_payments_bancontact',
            'description' => 'EMS Online Bancontact',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_bancontact.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);

        /**
         *  Install Bank Transfer Payment
         */
        $options = [
            'name' => 'emspa_payments_banktransfer',
            'description' => 'EMS Online Bank Transfer',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_banktransfer.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);

        /**
         *  Install Credit Card Payment
         */
        $options = [
            'name' => 'emspa_payments_creditcard',
            'description' => 'EMS Online Credit Card',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_creditcard.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);

        /**
         *  Install Payconiq Payment
         */
        $options = [
            'name' => 'emspa_payments_payconiq',
            'description' => 'EMS Online Payconiq',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_payconiq.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);

        /**
         *  Install PayPal Payment
         */
        $options = [
            'name' => 'emspa_payments_paypal',
            'description' => 'EMS Online PayPal',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_paypal.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);

        /**
         *  Install Tikkie Payment Request Payment
         */
        $options = [
            'name' => 'emspa_payments_tikkiepaymentrequest',
            'description' => 'EMS Online Tikkie Payment Request',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_tikkiepaymentrequest.png"/>'
                . '<div id="payment_desc">'
                . '  Pay using EMS Online.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);

        /**
         *  Install WeChat Payment
         */
        $options = [
            'name' => 'emspa_payments_wechat',
            'description' => 'EMS Online WeChat',
            'action' => 'emspayGateway',
            'active' => 0,
            'position' => 1,
            'additionalDescription' =>
                '<img src="custom/plugins/emspa_payments/Payment_description/ginger_wechat.png"/>'
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
        $context->scheduleClearCache($context::CACHE_LIST_ALL);
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), true);
        $context->scheduleClearCache($context::CACHE_LIST_ALL);
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
