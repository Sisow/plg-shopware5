<?php

use SisowPayment\Components\SisowPayment\SisowService;
use SisowPayment\Models\Transaction;
use Shopware\Components\Random;
use Shopware\Models\Customer;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use function Sodium\add;

class Shopware_Controllers_Frontend_Sisow extends Shopware_Controllers_Frontend_Payment
{
    public function preDispatch()
    {
        $this->get('template')->addTemplateDir($this->getTemplateDir());
    }

    /**
     * Index action method.
     *
     * Forwards to the correct action.
     */
    public function indexAction()
    {
        $templateFileName = 'sisow_' . $this->getPaymentCode() . '.tpl';

        if($this->view()->templateExists($templateFileName)) {

            // get sisow issuers
            $sisowConfig = $this->getConfig();
            $sisow = $this->getSisowService();
            $issuers = array();
            $sisow->DirectoryRequest($issuers, false, (bool)$sisowConfig['testmode']);

            // get max date
            $maxDate = new \DateTime();
            $maxDate->sub(new DateInterval("P18Y"));

            // b2b?
            $user = $this->getUser();
            $isB2b = isset($user['billingaddress']['company']);

            // get billing country
            $billingCountry = $this->getBillingCountryCode();

            // afterpay terms url
            $afterpayUrl = 'http://www.afterpay.nl/consument-betalingsvoorwaarden';

            if($isB2b && $billingCountry == 'NL'){
                $afterpayUrl = 'https://www.afterpay.nl/nl/algemeen/zakelijke-partners/betalingsvoorwaarden-zakelijk';
            }else if($billingCountry == 'BE'){
                $afterpayUrl = 'https://www.afterpay.be/be/footer/betalen-met-afterpay/betalingsvoorwaarden';
            }

            $this->View()->loadTemplate('sisow_core.tpl')->assign([
                'template' => $templateFileName,
                'issuers' => $issuers,
                'maxdate' => $maxDate->format('Y-m-d'),
                'b2b' => $isB2b,
                'phone' => $user['billingaddress']['phone'],
                'country' => $billingCountry,
                'afterpayUrl' => $afterpayUrl
            ]);
            return;
        }
        else {
            return $this->redirect(['controller' => 'Sisow', 'action' => 'pay']);
        }
    }

    public function payAction(){
        // get posted values
        $postValues = $this->Request()->getPost();

        // get config
        $sisowConfig = $this->getConfig();

        // get user
        $user = $this->getUser();
        $billingAddress = $user['billingaddress'];
        $shippingAddress = $user['shippingaddress'];
        $additionalInfo = $this->getAdditionalInfo();
        $shippingCountry = array_key_exists('countryShipping', $additionalInfo) ? $additionalInfo['countryShipping'] : false;
        $basket = $this->getBasket();

        $arg = array();

        // set user mail
        $emailAddress = array_key_exists('user', $additionalInfo) ? $additionalInfo['user']['email'] : '';

        // set billing address
        $arg['billing_firstname'] = $billingAddress['firstname'];
        $arg['billing_lastname'] = $billingAddress['lastname'];
        $arg['billing_mail'] = $emailAddress;
        $arg['billing_company'] = $billingAddress['company'];
        $arg['billing_address1'] = $billingAddress['street'];
        $arg['billing_address2'] = $billingAddress['additionalAddressLine1'];
        $arg['billing_zip'] = $billingAddress['zipcode'];
        $arg['billing_city'] = $billingAddress['city'];
        $arg['billing_countrycode'] = $this->getBillingCountryCode();
        $arg['billing_phone'] = array_key_exists('phone', $postValues) ? $postValues['phone'] : $billingAddress['phone'];

        // set shipping address
        $arg['shipping_firstname'] = $shippingAddress['firstname'];
        $arg['shipping_lastname'] = $shippingAddress['lastname'];
        $arg['shipping_mail'] = $emailAddress;
        $arg['shipping_company'] = $shippingAddress['company'];
        $arg['shipping_address1'] = $shippingAddress['street'];
        $arg['shipping_address2'] = $shippingAddress['additionalAddressLine1'];
        $arg['shipping_zip'] = $shippingAddress['zipcode'];
        $arg['shipping_city'] = $shippingAddress['city'];
        if(is_array($shippingCountry) && array_key_exists('countryiso', $shippingCountry))
            $arg['shipping_countrycode'] = $shippingCountry['countryiso'];
        $arg['shipping_phone'] = array_key_exists('phone', $postValues) ? $postValues['phone'] : $shippingAddress['phone'];

        // set products
        if(is_array($basket))
        {
            for($i=0; $i < count($basket['content']); $i++){
                $arg['product_id_' . ($i + 1)] = empty($basket['content'][$i]['ordernumber']) ? $basket['content'][$i]['articleID'] : $basket['content'][$i]['ordernumber'];
                $arg['product_description_' . ($i + 1)] = $basket['content'][$i]['articlename'];
                $arg['product_quantity_' . ($i + 1)] = $basket['content'][$i]['quantity'];
                $arg['product_netprice_' . ($i + 1)] = round($basket['content'][$i]['netprice'] * 100.0);
                $arg['product_total_' . ($i + 1)] = round($basket['content'][$i]['amountNumeric'] * 100.0);
                $arg['product_nettotal_' . ($i + 1)] = round($basket['content'][$i]['amountnetNumeric'] * 100.0);
                $arg['product_tax_' . ($i + 1)] = $arg['product_total_' . ($i + 1)] - $arg['product_nettotal_' . ($i + 1)];
                $arg['product_taxrate_' . ($i + 1)] = round($basket['content'][$i]['tax_rate'] * 100);
            }
        }

        // get locale
        $locale = Shopware()->Shop()->getLocale()->getLocale();

        // set common params
        $arg['locale'] = empty($locale) ? 'en' : strlen($locale) == 2 ? $locale : strlen($locale) == 5 ? substr($locale, 0, 2) : 'en';
        $arg['currency'] = $this->getCurrencyShortName();
        $arg['ipaddress'] = $this->Request()->getClientIp();

        // basket signature
        $signature = $this->persistBasket();

        // get unique payment id
        $uniquePaymentId = $this->createPaymentUniqueId();

        // get description
        $description = $sisowConfig['description'];
        if(empty($description)){
            $description = 'Order [name]';
        }

        // replace [name] with shopname
        $description = str_replace('[name]', $this->get('config')->get('shopname'), $description);

        // get sisow service
        $sisow = $this->getSisowService();
        $sisow->purchaseId = Random::getAlphanumericString(16);
        $sisow->entranceCode = $uniquePaymentId;
        $sisow->description = $description;
        $sisow->payment = $this->getPaymentCode();
        $sisow->amount = $this->getAmount();
        $sisow->notifyUrl = $this->Front()->Router()->assemble(['controller' => 'Sisow', 'action' => 'notify']) . '?signature=' . $signature;
        $sisow->callbackUrl = $sisow->notifyUrl;
        $sisow->returnUrl = $this->Front()->Router()->assemble(['controller' => 'Sisow', 'action' => 'return']) . '?signature=' . $signature;
        $sisow->cancelUrl = $sisow->returnUrl;

        // set issuer if set
        if(array_key_exists('issuer', $postValues)){
            $sisow->issuerId = $postValues['issuer'];
        }

        // set bic if set
        if(array_key_exists('bic', $postValues)){
            $arg['bic'] = $postValues['bic'];
        }

        // set bic if set
        if(array_key_exists('iban', $postValues)){
            $arg['iban'] = $postValues['iban'];
        }

        // set bic if set
        if(array_key_exists('gender', $postValues)){
            $arg['gender'] = $postValues['gender'];
        }

        // set bic if set
        if(array_key_exists('dob', $postValues)){
            $dateParts = explode('-', $postValues['dob']);

            if(count($dateParts) == 3) {
                $arg['birthdate'] = $dateParts[2] . $dateParts[1] . $dateParts[0];
            }
        }

        // set coc if set
        if(array_key_exists('coc', $postValues)){
            $arg['billing_coc'] = $postValues['coc'];
        }

        // set testmode
        $arg['testmode'] = (bool)$sisowConfig['testmode'] ? 'true' : 'false';

        // start payment
        if(($ex = $sisow->TransactionRequest($arg)) < 0){
            // TODO: set error message for user
            print_r($sisow);
            exit;

            return $this->redirect(['controller' => 'checkout']);
        }

        // get customer
        $customerRepository = $this->container->get('models')->getRepository(Customer\Customer::class);

        $arrUser = $this->getUser();
        $customer = $customerRepository->find($arrUser['additional']['user']['id']);

        // get comment & dispatch
        $comment = empty(Shopware()->Session()->sComment) ? '' : Shopware()->Session()->sComment;
        $dispatch = empty(Shopware()->Session()->sDispatch) ? '' : Shopware()->Session()->sDispatch;

        $transaction = $this->container->get('models')->getRepository(Transaction::class);
        $transaction->createNew($customer, $comment, $dispatch, $uniquePaymentId);

        // get transaction status
        if($sisow->StatusRequest() == 0 && $sisow->status != 'Open'){
            $this->setOrderStatus($sisow->status, $uniquePaymentId, $sisow->trxId, $signature);

            $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
        }else {
            return $this->redirect($sisow->issuerUrl);
            exit;
        }
    }
	
	private function getSisowService()
	{
		// load sisow class
		$sisow = $this->container->get('sisow_payment.sisow_service');

		$config = $this->getConfig();

		// set merchant
		$merchantId = $config['merchantid'];
		$merchantKey = $config['merchantkey'];
		$shopId = $config['shopid'];
		
		$sisow->setMerchant($merchantId, $merchantKey, $shopId);
		
		return $sisow;
	}

	public function notifyAction(){
        // get url params
        $paymentUniqueId = $this->Request()->getParam('ec', '');
        $trxId = $this->Request()->getParam('trxid', '');
        $basketSignature = $this->Request()->getParam('signature', '');

        if(!$this->validateUrl(true)){
            echo 'Invalid URL';
            exit;
        }

        // get sisow object
        $sisow = $this->getSisowService();

        // get status
        if(($ex = $sisow->StatusRequest($trxId)) < 0){
            //TODO: return badrequest
            exit;
        }

        $this->setOrderStatus($sisow->status, $paymentUniqueId, $trxId, $basketSignature);
        exit;
    }

    /**
     * Return action method
     *
     * Reads the transactionResult and represents it for the customer.
     */
    public function returnAction()
    {
        // load URL params
        $status = $this->Request()->getParam('status');
        $uniquePaymentId = $this->Request()->getParam('ec');

        if(!$this->validateUrl(false)){
            echo 'Invalid URL';
            exit;
        }

        // states to redirect to thank you page
        $success_status = ['Reservation', 'Paid', 'Success', 'Open', 'Pending'];

        if(in_array($status, $success_status)){
            // get transaction
            $transactionRepository = $this->container->get('models')->getRepository(Transaction::class);
            $transaction = $transactionRepository->findOneBy(['uniquePaymentId' => $uniquePaymentId]);

            // restore session
            // remove the basket
            Shopware()->Modules()->Basket()->clearBasket();
            $sOrderVariables = Shopware()->Session()->offsetGet('sOrderVariables');
            $sOrderVariables['sOrderNumber'] = $transaction->getOrder()->getNumber();

            Shopware()->Session()->offsetSet('sOrderVariables', $sOrderVariables);

            $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
        }
        else{
            return $this->redirect(
                Shopware()->Front()->Router()->assemble([
                    'controller' => 'checkout',
                    'action' => 'confirm'
                ])
            );
        }
    }

    private function setOrderStatus($sisowStatus, $paymentUniqueId, $trxId, $basketSignature = false){
        // set status
        switch($sisowStatus){
            case 'Reservation':
                $status = Status::PAYMENT_STATE_THE_CREDIT_HAS_BEEN_ACCEPTED;
                break;
            case 'Paid':
            case 'Success':
                $status = Status::PAYMENT_STATE_COMPLETELY_PAID;
                break;
            case 'Open':
            case 'Pending':
                $status = Status::PAYMENT_STATE_OPEN;
                break;
            default:
                $status = null;
                break;
        }

        if(!empty($status)) {
            // get transaction
            $transactionRepository = $this->container->get('models')->getRepository(Transaction::class);
            $transaction = $transactionRepository->findOneBy(['uniquePaymentId' => $paymentUniqueId]);

            // restore session
            $this->get('session')->sUserId = $transaction->getCustomer()->getId();
            $this->get('session')->sComment = $transaction->getSComment();
            $this->get('session')->sDispatch = $transaction->getSDispatch();

            // restore basket
            $transaction->getSDispatch();

            if($basketSignature) {
                $basket = $this->loadBasketFromSignature($basketSignature);
                $this->verifyBasketSignature($basketSignature, $basket);
            }

            // save order
            $orderNumber = $this->saveOrder(
                $trxId,
                $paymentUniqueId,
                $status,
                true
            );

            // load order
            $orderRepository = $this->container->get('models')->getRepository(Order::class);
            $order = $orderRepository->findOneBy(['number' => $orderNumber]);

            // set order to transaction
            $transaction->setOrder($order);

            // save transaction
            $transactionRepository->save($transaction);

            // update PurchaseId by Sisow
            $sisow = $this->getSisowService();
            $sisow->AdjustPurchaseId($trxId, $sisow->purchaseId, $orderNumber);
        }
    }

    private function getConfig(){
        $shop = false;
        if ($this->container->initialized('shop')) {
            $shop = $this->container->get('shop');
        }

        if (!$shop) {
            $shop = $this->container->get('models')->getRepository(\Shopware\Models\Shop\Shop::class)->getActiveDefault();
        }

        return $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('SisowPayment', $shop);
    }

    private function validateUrl(bool $notifyUrl){
        $ec = $this->Request()->getParam('ec', '');
        $trxId = $this->Request()->getParam('trxid', '');
        $status = $this->Request()->getParam('status', '');
        $urlSha1 = $this->Request()->getParam('sha1', '');

        $notify = $this->Request()->getParam('notify', '');
        $callback = $this->Request()->getParam('callback', '');

        // load config
        $config = $this->getConfig();
        $merchantId = $config['merchantid'];
        $merchantKey = $config['merchantkey'];

        return $urlSha1 == sha1($trxId . $ec . $status . $merchantId . $merchantKey) && (($notifyUrl && ($notify == 'true' || $callback == 'true')) || !$notifyUrl);
    }

    private function getTemplateDir(){
        /** @var \Shopware\Components\Plugin $plugin */
        $plugin = $this->get('kernel')->getPlugins()['SisowPayment'];

        return $plugin->getPath() . '/Resources/views/frontend/template';
    }

    private function getPaymentCode(){
        return substr($this->getPaymentShortName(), 6);
    }

    private function getBillingCountryCode(){
        $additionalInfo = $this->getAdditionalInfo();

        $billingCountry = array_key_exists('country', $additionalInfo) ? $additionalInfo['country'] : false;

        if(is_array($billingCountry) && array_key_exists('countryiso', $billingCountry))
            return strtoupper($billingCountry['countryiso']);
        else
            return '';
    }

    private function getAdditionalInfo(){
        $user = $this->getUser();
        return array_key_exists('additional', $user) ? $user['additional'] : array();
    }
}
