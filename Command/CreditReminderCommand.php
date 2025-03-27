<?php

namespace CreditReminder\Command; 

use CreditAccount\Model\CreditAccountExpirationQuery;
use CreditAccount\Model\CreditAccountQuery;
use CreditReminder\CreditReminder;
use CreditReminder\Event\CreditReminderEvent;
use CreditReminder\Event\CreditReminderEvents;
use CreditReminder\Model\CreditReminderQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Command\ContainerAwareCommand;
use Thelia\Model\CustomerQuery;
use Propel\Runtime\ActiveQuery\Criteria;

class CreditReminderCommand extends ContainerAwareCommand
{
    /** @var EventDispatcherInterface */
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct();
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName("credit:send-reminders")
            ->setDescription("Send reminder emails to customers with expiring loyalty credits");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \DateMalformedIntervalStringException
     * @throws \DateMalformedStringException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initRequest();
        $output->writeln("<info>Starting credit reminder process</info>");

        // Configuration values
        $daysBeforeExpiration = (int)CreditReminder::getConfigValue('reminder_days_before', 30);
        $reminderIntervalDays = (int)CreditReminder::getConfigValue('reminder_interval_days', 10);

        // Calculate expiration date thresholdgetExpirationDate
        $expirationThreshold = new \DateTime();
        $expirationThreshold->add(new \DateInterval('P' . $daysBeforeExpiration . 'D'));
        $expirationDate = $expirationThreshold->format('Y-m-d');

        $customersWithCredit = CreditAccountQuery::create()
            ->useCreditAccountExpirationQuery()
                ->filterByExpirationStart($expirationDate, Criteria::LESS_EQUAL)
                ->filterByExpirationStart(date('Y-m-d'), Criteria::GREATER_THAN)
            ->endUse()
            ->filterByAmount(0, Criteria::GREATER_THAN)
            ->find()
        ;

        $output->writeln(sprintf("<info>Found %d customers with expiring credits</info>", count($customersWithCredit)));
        $emailsSent = 0;

        foreach ($customersWithCredit as $creditAccount) {
            $customerId = $creditAccount->getCustomerId();
            
            // Check if customer exists
            $customer = CustomerQuery::create()->findPk($customerId);
            if (null === $customer) {
                $output->writeln("<error>Customer ID $customerId not found, skipping</error>");
                continue;
            }

            $creditAccountExpiration = CreditAccountExpirationQuery::create()
                ->filterById($creditAccount->getId())
                ->findOne();

            // Check if enough time has passed since last email
            $reminderRecord = CreditReminderQuery::create()
                ->filterByCustomerId($customerId)
                ->findOneOrCreate();

            $lastSentDate = $reminderRecord->getLastSentDate();
            $canSendEmail = true;
            
            if (null !== $lastSentDate) {
                $lastSent = $lastSentDate instanceof \DateTime ? $lastSentDate : new \DateTime($lastSentDate);
                $today = new \DateTime();
                $daysSinceLastEmail = $today->diff($lastSent)->days;
                
                if ($daysSinceLastEmail < $reminderIntervalDays) {
                    $output->writeln("Not enough time passed since last email to customer $customerId, skipping");
                    $canSendEmail = false;
                }
            }

            if ($canSendEmail) {
                try {
                    // Create and dispatch the event
                    $event = new CreditReminderEvents(
                        $customer, 
                        $creditAccount, 
                        new \DateTime($creditAccountExpiration?->getExpirationStart())
                    );
                    
                    $this->eventDispatcher->dispatch(
                        $event, 
                        CreditReminder::CREDIT_REMINDER_SEND_EMAIL
                    );

                    // Check if email was sent
                    if ($event->isEmailSent()) {
                        $output->writeln("<info>Email sent to customer $customerId</info>");
                        $emailsSent++;
                    }
                } catch (\Exception $e) {
                    $output->writeln("<error>Failed to send email to customer $customerId: " . $e->getMessage() . "</error>");
                }
            }
        }

        $output->writeln("<info>Credit reminder process completed. $emailsSent emails sent.</info>");
        return Command::SUCCESS;
    }
}