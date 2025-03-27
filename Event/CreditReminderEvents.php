<?php

namespace CreditReminder\Event;

use Thelia\Core\Event\ActionEvent;
use Thelia\Model\Customer;
use CreditAccount\Model\CreditAccount;

class CreditReminderEvents extends ActionEvent
{
    /** @var Customer */
    protected Customer $customer;

    /** @var CreditAccount */
    protected CreditAccount $creditAccount;

    /** @var \DateTime */
    protected \DateTime $expirationDate;

    /** @var bool */
    protected bool $emailSent = false;

    /**
     * @param Customer $customer
     * @param CreditAccount $creditAccount
     * @param \DateTime $expirationDate
     */
    public function __construct(Customer $customer, CreditAccount $creditAccount, \DateTime $expirationDate)
    {
        $this->customer = $customer;
        $this->creditAccount = $creditAccount;
        $this->expirationDate = $expirationDate;
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * @return CreditAccount
     */
    public function getCreditAccount(): CreditAccount
    {
        return $this->creditAccount;
    }

    /**
     * @return \DateTime
     */
    public function getExpirationDate(): \DateTime
    {
        return $this->expirationDate;
    }

    /**
     * @return bool
     */
    public function isEmailSent(): bool
    {
        return $this->emailSent;
    }

    /**
     * @param bool $emailSent
     * @return $this
     */
    public function setEmailSent($emailSent): static
    {
        $this->emailSent = $emailSent;
        return $this;
    }
}