<?php

namespace CreditReminder\Model;

use CreditReminder\Model\Base\CreditReminder as BaseCreditReminder;
use Propel\Runtime\Connection\ConnectionInterface;
use Thelia\Model\CustomerQuery;

class CreditReminder extends BaseCreditReminder
{
    /**
     * Get customer name
     *
     * @return string
     */
    public function getCustomerName(): string
    {
        $customer = CustomerQuery::create()->findPk($this->getCustomerId());
        
        if (null === $customer) {
            return 'Unknown';
        }
        
        return $customer->getFirstname() . ' ' . $customer->getLastname();
    }
    
    /**
     * Get customer email
     *
     * @return string
     */
    public function getCustomerEmail(): string
    {
        $customer = CustomerQuery::create()->findPk($this->getCustomerId());
        
        if (null === $customer) {
            return 'Unknown';
        }
        
        return $customer->getEmail();
    }


}
