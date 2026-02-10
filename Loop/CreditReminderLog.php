<?php

namespace CreditReminder\Loop;

use CreditReminder\Model\CreditReminderLogQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

class CreditReminderLog extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('customer_id'),
            Argument::createBooleanTypeArgument('is_test'),
            Argument::createEnumListTypeArgument(
                'order',
                [
                    'id',
                    'id-reverse',
                    'sent-at',
                    'sent-at-reverse',
                    'created-at',
                    'created-at-reverse',
                ],
                'sent-at-reverse'
            )
        );
    }

    public function buildModelCriteria()
    {
        $query = CreditReminderLogQuery::create();

        if (null !== $customerId = $this->getCustomerId()) {
            $query->filterByCustomerId($customerId);
        }

        if (null !== $isTest = $this->getIsTest()) {
            $query->filterByIsTest($isTest);
        }

        $orders = $this->getOrder();

        foreach ($orders as $order) {
            switch ($order) {
                case 'id':
                    $query->orderById(Criteria::ASC);
                    break;
                case 'id-reverse':
                    $query->orderById(Criteria::DESC);
                    break;
                case 'sent-at':
                    $query->orderBySentAt(Criteria::ASC);
                    break;
                case 'sent-at-reverse':
                    $query->orderBySentAt(Criteria::DESC);
                    break;
                case 'created-at':
                    $query->orderByCreatedAt(Criteria::ASC);
                    break;
                case 'created-at-reverse':
                    $query->orderByCreatedAt(Criteria::DESC);
                    break;
            }
        }

        return $query;
    }

    public function parseResults(LoopResult $loopResult)
    {
        /** @var \CreditReminder\Model\CreditReminderLog $log */
        foreach ($loopResult->getResultDataCollection() as $log) {
            $loopResultRow = new LoopResultRow($log);

            $customer = $log->getCustomer();

            $loopResultRow
                ->set('ID', $log->getId())
                ->set('CUSTOMER_ID', $log->getCustomerId())
                ->set('CUSTOMER_NAME', $customer ? $customer->getFirstname() . ' ' . $customer->getLastname() : '')
                ->set('EMAIL', $log->getEmail())
                ->set('CREDIT_AMOUNT', $log->getCreditAmount())
                ->set('EXPIRATION_DATE', $log->getExpirationDate() ? $log->getExpirationDate()->format('Y-m-d') : '')
                ->set('SENT_AT', $log->getSentAt() ? $log->getSentAt()->format('Y-m-d H:i:s') : '')
                ->set('IS_TEST', $log->getIsTest())
                ->set('CREATED_AT', $log->getCreatedAt() ? $log->getCreatedAt()->format('Y-m-d H:i:s') : '');

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
