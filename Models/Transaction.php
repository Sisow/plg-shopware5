<?php

namespace SisowPayment\Models;

use Symfony\Component\Validator\Constraints as Assert,
    Doctrine\Common\Collections\ArrayCollection,
    Shopware\Components\Model\ModelEntity,
    Doctrine\ORM\Mapping AS ORM,
    Shopware\Models\Order\Order;

/**
 * Class Transaction
 * @package SisowPayment\Models
 * @ORM\Entity(repositoryClass="SisowPayment\Models\TransactionRepository")
 * @ORM\Table(name="s_sisow_transactions")
 */
class Transaction
{
    /**
     * @var int
     * @ORM\Column(name="id", nullable=false, type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="uniquepaymentid", unique=true)
     */
    private $uniquePaymentId;

    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Customer\Customer")
     * @ORM\JoinColumn(nullable=false)
     */
    private $customer;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="s_comment", nullable=true)
     */
    private $sComment;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="s_dispatch", nullable=true)
     */
    private $sDispatch;

    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Order\Order")
     * @ORM\JoinColumn(nullable=true)
     */
    private $order;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSComment()
    {
        return $this->sComment;
    }

    /**
     * @param string $sComment
     */
    public function setSComment($sComment)
    {
        $this->sComment = $sComment;
    }

    /**
     * @return string
     */
    public function getSDispatch()
    {
        return $this->sDispatch;
    }
    /**
     * @param string $sDispatch
     */
    public function setSDispatch($sDispatch)
    {
        $this->sDispatch = $sDispatch;
    }
    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }
    /**
     * @param Customer $customer
     */
    public function setCustomer(\Shopware\Models\Customer\Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @param string $uniquePaymentId
     */
    public function setUniquePaymentId($uniquePaymentId)
    {
        $this->uniquePaymentId = $uniquePaymentId;
    }

    /**
     * @return string
     */
    public function getUniquePaymentId()
    {
        return $this->uniquePaymentId;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
    }
}