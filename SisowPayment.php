<?php

namespace SisowPayment;

use Doctrine\ORM\Tools\SchemaTool;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Payment\Payment;
use SisowPayment\Models\Transaction;

class SisowPayment extends Plugin
{
    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        // install payment methods
        $this->installPaymentMethod($context, 'ideal', 'iDEAL');
        $this->installPaymentMethod($context, 'idealqr', 'iDEAL QR');
        $this->installPaymentMethod($context, 'overboeking', 'Bankoverboeking');
        $this->installPaymentMethod($context, 'bunq', 'bunq');
        $this->installPaymentMethod($context, 'creditcard', 'Creditcard');
        $this->installPaymentMethod($context, 'maestro', 'Maestro');
        $this->installPaymentMethod($context, 'vpay', 'V PAY');
        $this->installPaymentMethod($context, 'sofort', 'SOFORT Banking');
        $this->installPaymentMethod($context, 'giropay', 'Giropay');
        $this->installPaymentMethod($context, 'eps', 'EPS');
        $this->installPaymentMethod($context, 'bancontact', 'Bancontact');
        $this->installPaymentMethod($context, 'belfius', 'Belfius Pay Button');
        $this->installPaymentMethod($context, 'cbc', 'CBC');
        $this->installPaymentMethod($context, 'kbc', 'KBC');
        $this->installPaymentMethod($context, 'homepay', 'ING Home\'Pay');
        $this->installPaymentMethod($context, 'paypalec', 'PayPal Express Checkout');
        $this->installPaymentMethod($context, 'afterpay', 'Afterpay');
        $this->installPaymentMethod($context, 'billink', 'Billink Achteraf Betalen');
        $this->installPaymentMethod($context, 'focum', 'Focum Achteraf Betalen');
        $this->installPaymentMethod($context, 'capayable', 'IN3 Gespreid Betalen');
        $this->installPaymentMethod($context, 'spraypay', 'Spraypay');
        $this->installPaymentMethod($context, 'vvv', 'VVV Giftcard');
        $this->installPaymentMethod($context, 'webshop', 'Webshop Giftcard');

        $this->updateDB();

        $this->clearCache();
    }

    private function installPaymentMethod($context, $code, $name){
        // path to template dir
        $paymentTemplateDir = __DIR__ . '/Resources/views/frontend/template';

        // template path for payment method
        $paymentTemplate = $paymentTemplateDir . '/' . $code . '.tpl';

        // template exists
        if (!file_exists($paymentTemplate)) {
            $paymentTemplate = '';
        }

        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        $img_url = (isset($_SERVER['HTTP_HOST']) ? $schema . '://' . $_SERVER['HTTP_HOST'] : '') . '/custom/plugins/SisowPayment/Resources/views/frontend/images/'. $code;
        $options = [
            'name' => 'sisow_' . $code,
            'description' => 'Buckaroo ' . $name,
            'action' => 'Sisow',
            'active' => 0,
            'position' => 0,
            'additionalDescription' =>
                '<img src="'.$img_url.'.png"/>'
                . '<div id="payment_desc">'
                . '  Pay save and secured with ' . $name . ', provided by Buckaroo.'
                . '</div>'
        ];

        if(!empty($paymentTemplate)){
            $options['template'] = $code . '.tpl';
        }

        $installer->createOrUpdate($context->getPlugin(), $options);
    }

    public function update(UpdateContext $context){
        $this->updateDB();

        $this->clearCache();
    }

    public function updateDB(){
        // update database tables
        $modelManager = $this->container->get('models');
        $tool = new SchemaTool($modelManager);
        $classes = [
            $modelManager->getClassMetadata(Transaction::class)
        ];

        $tool->updateSchema($classes, true);
    }

    private function clearCache(){
        $cache = \Shopware\Components\Api\Manager::getResource('cache');
        $cache->delete('all');
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
