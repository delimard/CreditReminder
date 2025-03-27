<?php

namespace CreditReminder\Loop;

use CreditReminder\Model\CreditReminderQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Exception\PropelException;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\CustomerQuery;

class CreditReminderLoop extends BaseLoop implements PropelSearchLoopInterface
{
    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('customer_id'),
            Argument::createIntTypeArgument('emails_sent'),
            Argument::createAnyTypeArgument('order_by', 'last_sent_date'),
            Argument::createBooleanTypeArgument('order_reverse', false)
        );
    }

    /**
     * @return CreditReminderQuery|ModelCriteria
     */
    public function buildModelCriteria(): CreditReminderQuery|ModelCriteria
    {
        $query = CreditReminderQuery::create();

        if ($customerId = $this->getCustomerId()) {
            $query->filterByCustomerId($customerId);
        }

        if (null !== $emailsSent = $this->getEmailsSent()) {
            $query->filterByEmailsSent($emailsSent);
        }

        $orderBy = $this->getOrderBy();
        $reverse = $this->getOrderReverse();

        switch ($orderBy) {
            case 'last_sent_date':
                $query->orderByLastSentDate($reverse ? Criteria::DESC : Criteria::ASC);
                break;
            case 'emails_sent':
                $query->orderByEmailsSent($reverse ? Criteria::DESC : Criteria::ASC);
                break;
            case 'customer':
                // We can't directly order by customer name as it's in another table
                // Default to ordering by customer ID
                $query->orderByCustomerId($reverse ? Criteria::DESC : Criteria::ASC);
                break;
            default:
                $query->orderByLastSentDate(Criteria::DESC);
        }

        return $query;
    }

    /**
     * @param LoopResult $loopResult
     * @return LoopResult
     * @throws PropelException
     */
    public function parseResults(LoopResult $loopResult): LoopResult
    {
        /** @var \CreditReminder\Model\CreditReminder $reminder */
        foreach ($loopResult->getResultDataCollection() as $reminder) {
            $loopResultRow = new LoopResultRow($reminder);
            
            $customer = CustomerQuery::create()->findPk($reminder->getCustomerId());
            $customerName = $customer ? $customer->getFirstname() . ' ' . $customer->getLastname() : 'Unknown';
            $customerEmail = $customer ? $customer->getEmail() : 'Unknown';
            
            $loopResultRow->set('ID', $reminder->getId())
                ->set('CUSTOMER_ID', $reminder->getCustomerId())
                ->set('CUSTOMER_NAME', $customerName)
                ->set('CUSTOMER_EMAIL', $customerEmail)
                ->set('EMAILS_SENT', $reminder->getEmailsSent())
                ->set('LAST_SENT_DATE', $reminder->getLastSentDate('Y-m-d H:i:s'))
                ->set('CREATED_AT', $reminder->getCreatedAt('Y-m-d H:i:s'));
                
            $loopResult->addRow($loopResultRow);
        }
        
        return $loopResult;
    }
}
