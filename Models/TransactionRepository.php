<?php


namespace SisowPayment\Models;

use Shopware\Components\Model\ModelRepository;
use DateTime;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;

class TransactionRepository extends ModelRepository
{
    /**
     * Initialize a new transaction
     *
     * @param Customer $customer
     * @param string $sComment
     * @param string $sDispatch
     * @param string $sUniquePaymentId
     * @return Transaction
     * @throws \Exception
     */
    public function createNew(Customer $customer, string $sComment, string $sDispatch, string $sUniquePaymentId){
        $now = new DateTime();
        $transaction = new Transaction();
        $transaction->setCustomer($customer);
        $transaction->setSComment($sComment);
        $transaction->setSDispatch($sDispatch);
        $transaction->setUniquePaymentId($sUniquePaymentId);
        $this->save($transaction);
        return $transaction;
    }
    public function save(Transaction $transaction){
        $this->getEntityManager()->persist($transaction);
        $this->getEntityManager()->flush();
    }
}