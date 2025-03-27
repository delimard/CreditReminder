<?php

namespace CreditReminder\EventListeners;

use CreditReminder\CreditReminder;
use CreditReminder\Event\CreditReminderEvents;
use CreditReminder\Model\CreditReminderQuery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Template\ParserInterface;
use Thelia\Mailer\MailerFactory;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Lang;
use Thelia\Model\MessageQuery;

class CreditChangeListener implements EventSubscriberInterface
{
    /** @var MailerFactory */
    protected MailerFactory $mailer;

    /** @var ParserInterface */
    protected ParserInterface $parser;

    /** @var Request */
    protected Request $request;

    /**
     * @param MailerFactory $mailer
     * @param ParserInterface $parser
     * @param Request $request
     */
    public function __construct(MailerFactory $mailer, ParserInterface $parser, Request $request)
    {
        $this->mailer = $mailer;
        $this->parser = $parser;
        $this->request = $request;
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CreditReminder::CREDIT_REMINDER_SEND_EMAIL => ['sendCreditReminderEmail', 128]
        ];
    }

    /**
     * @param CreditReminderEvents $event
     * @return void
     * @throws \Exception
     */
    public function sendCreditReminderEmail(CreditReminderEvents $event): void
    {
        $customer = $event->getCustomer();
        $creditAccount = $event->getCreditAccount();
        $expirationDate = $event->getExpirationDate();

        // Get maximum emails allowed
        $maxEmails = (int)CreditReminder::getConfigValue('reminder_max_emails', 2);

        // Check if we already sent reminders to this customer
        $reminderRecord = CreditReminderQuery::create()
            ->filterByCustomerId($customer->getId())
            ->findOneOrCreate();

        // Skip if maximum emails already sent
        if ($reminderRecord->getEmailsSent() >= $maxEmails) {
            return;
        }

        // Get message from database
        $message = MessageQuery::create()
            ->filterByName('credit_reminder_message')
            ->findOne();

        if (null === $message) {
            throw new \RuntimeException(
                "The credit_reminder_message does not exist. Please create this message in Thelia back-office."
            );
        }

        // Set customer preferred locale/language
        $lang = $customer->getCustomerLang();
        if (null === $lang) {
            $lang = Lang::getDefaultLanguage();
        }
        
        $this->request->getSession()?->setLang($lang);
        
        // Prepare contact information
        $contactEmail = ConfigQuery::getStoreEmail();
        $storeName = ConfigQuery::getStoreName();

        try {
            // Send message
            $this->mailer->sendEmailMessage(
                $message->getName(),
                [$contactEmail => $storeName],
                [$customer->getEmail() => $customer->getFirstname() . ' ' . $customer->getLastname()],
                [
                    'customer_id' => $customer->getId(),
                    'customer_firstname' => $customer->getFirstname(),
                    'customer_lastname' => $customer->getLastname(),
                    'credit_amount' => $creditAccount->getAmount(),
                    'expiration_date' => $expirationDate->format('Y-m-d'),
                ]
            );

            // Update reminder record
            $reminderRecord->setEmailsSent($reminderRecord->getEmailsSent() + 1);
            $reminderRecord->setLastSentDate(date('Y-m-d H:i:s'));
            $reminderRecord->save();

            // Mark email as sent
            $event->setEmailSent(true);
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            throw $e;
        }
    }
}