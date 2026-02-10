<?php

namespace CreditReminder\Service;

use CreditAccount\Model\CreditAccountExpirationQuery;
use CreditAccount\Model\CreditAccountQuery;
use CreditReminder\CreditReminder;
use CreditReminder\Model\CreditReminderLog;
use CreditReminder\Model\CreditReminderLogQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Mailer\MailerFactory;
use Thelia\Model\ConfigQuery;
use Thelia\Model\CustomerQuery;
use Thelia\Model\MessageQuery;

class CreditReminderService
{
    protected $mailerFactory;
    protected $eventDispatcher;

    public function __construct(
        MailerFactory $mailerFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->mailerFactory = $mailerFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Calcule la date d'expiration d'un compte crédit
     * 
     * @param \CreditAccount\Model\CreditAccount $creditAccount
     * @return \DateTime|null
     */
    protected function getExpirationDate($creditAccount)
    {
        if (!$creditAccount) {
            return null;
        }

        // Récupérer l'expiration associée
        $expiration = CreditAccountExpirationQuery::create()
            ->filterByCreditAccountId($creditAccount->getId())
            ->findOne();

        if (!$expiration) {
            return null;
        }

        // Calculer la date d'expiration : expiration_start + expiration_delay (en mois)
        $expirationStart = $expiration->getExpirationStart();
        $expirationDelay = $expiration->getExpirationDelay();

        if (!$expirationStart || !$expirationDelay) {
            return null;
        }

        $expirationDate = clone $expirationStart;
        $expirationDate->modify("+{$expirationDelay} months");

        return $expirationDate;
    }

    /**
     * Récupère les clients éligibles pour un rappel de crédit
     * 
     * @param int $daysBeforeExpiration Nombre de jours avant expiration
     * @param int|null $limit Nombre maximum d'emails à envoyer
     * @return array
     */
    public function getEligibleCustomers($daysBeforeExpiration, $limit = null)
    {
        $con = Propel::getConnection();
        
        // Date cible : dans X jours
        $targetDate = new \DateTime();
        $targetDate->add(new \DateInterval('P' . $daysBeforeExpiration . 'D'));
        $targetDateStr = $targetDate->format('Y-m-d');

        // Date actuelle
        $today = new \DateTime();
        $todayStr = $today->format('Y-m-d');

        // Récupérer tous les comptes crédit avec un montant > 0
        $creditAccounts = CreditAccountQuery::create()
            ->filterByAmount(0, Criteria::GREATER_THAN)
            ->find($con);

        $eligibleCustomers = [];
        $processedCustomers = [];

        foreach ($creditAccounts as $creditAccount) {
            // Si on a atteint la limite, on arrête
            if ($limit !== null && count($eligibleCustomers) >= $limit) {
                break;
            }

            $customerId = $creditAccount->getCustomerId();

            // Si on a déjà traité ce client, on passe au suivant
            if (in_array($customerId, $processedCustomers)) {
                continue;
            }

            // Calculer la date d'expiration
            $expirationDate = $this->getExpirationDate($creditAccount);

            // Si pas de date d'expiration, on passe
            if (!$expirationDate) {
                continue;
            }

            // Vérifier si la date d'expiration est dans la fenêtre cible
            $expirationDateStr = $expirationDate->format('Y-m-d');
            
            // Le crédit doit expirer entre aujourd'hui et dans X jours
            if ($expirationDateStr > $todayStr && $expirationDateStr <= $targetDateStr) {
                // Vérifier si le client existe
                $customer = CustomerQuery::create()->findPk($customerId);
                if (null === $customer) {
                    continue;
                }

                // Vérifier si un email a déjà été envoyé récemment
                $reminderIntervalDays = intval(CreditReminder::getConfigValue(CreditReminder::REMINDER_INTERVAL_DAYS, 10));
                
                $lastLog = CreditReminderLogQuery::create()
                    ->filterByCustomerId($customerId)
                    ->filterByIsTest(false)
                    ->orderBySentAt(Criteria::DESC)
                    ->findOne($con);

                $canSendEmail = true;
                if ($lastLog) {
                    $lastSent = $lastLog->getSentAt();
                    $daysSinceLastEmail = $today->diff($lastSent)->days;
                    
                    if ($daysSinceLastEmail < $reminderIntervalDays) {
                        $canSendEmail = false;
                    }
                }

                if ($canSendEmail) {
                    $eligibleCustomers[] = [
                        'customer' => $customer,
                        'creditAccount' => $creditAccount,
                        'expirationDate' => $expirationDate
                    ];
                    $processedCustomers[] = $customerId;
                }
            }
        }

        return $eligibleCustomers;
    }

    /**
     * Envoie un email de rappel pour un crédit
     * 
     * @param \Thelia\Model\Customer $customer
     * @param \CreditAccount\Model\CreditAccount $creditAccount
     * @param \DateTime $expirationDate
     * @param bool $isTest
     * @return bool
     */
    public function sendReminderEmail($customer, $creditAccount, $expirationDate, $isTest = false)
    {
        try {
            if (!$customer || !$creditAccount || !$expirationDate) {
                return false;
            }

            $message = MessageQuery::create()
                ->findOneByName(CreditReminder::CREDIT_REMINDER_MESSAGE_NAME);

            if (!$message) {
                return false;
            }

            // Préparer les données pour le template
            $parserContext = [
                'customer' => $customer,
                'credit_account' => $creditAccount,
                'credit_amount' => $creditAccount->getAmount(),
                'expiration_date' => $expirationDate,
                'store_name' => ConfigQuery::read('store_name'),
            ];

            // Récupérer la locale du client
            $locale = $customer->getCustomerLang() ? $customer->getCustomerLang()->getLocale() : 'fr_FR';

            // Envoyer l'email
            $this->mailerFactory->sendEmailMessage(
                $message->getName(),
                [ConfigQuery::read('store_email') => ConfigQuery::read('store_name')],
                [$customer->getEmail() => $customer->getFirstname() . ' ' . $customer->getLastname()],
                $parserContext,
                $locale
            );

            // Logger l'envoi
            $log = new CreditReminderLog();
            $log->setCustomerId($customer->getId())
                ->setEmail($customer->getEmail())
                ->setCreditAmount($creditAccount->getAmount())
                ->setExpirationDate($expirationDate)
                ->setSentAt(new \DateTime())
                ->setIsTest($isTest)
                ->save();

            return true;

        } catch (\Exception $e) {
            // Log l'erreur
            \Thelia\Log\Tlog::getInstance()->addError("Erreur lors de l'envoi du rappel de crédit : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie un email de test
     * 
     * @param string $email
     * @param int|null $customerId
     * @return bool
     */
    public function sendTestEmail($email, $customerId = null)
    {
        try {
            // Récupérer un client spécifique ou un client avec crédit pour l'exemple
            if ($customerId) {
                $customer = CustomerQuery::create()
                    ->filterById($customerId)
                    ->findOne();
            } else {
                // Trouver un client avec un compte crédit
                $creditAccount = CreditAccountQuery::create()
                    ->filterByAmount(0, Criteria::GREATER_THAN)
                    ->findOne();
                
                if ($creditAccount) {
                    $customer = $creditAccount->getCustomer();
                } else {
                    // Si aucun compte crédit, prendre n'importe quel client
                    $customer = CustomerQuery::create()->findOne();
                }
            }

            if (!$customer) {
                return false;
            }

            // Récupérer le compte crédit du client
            $creditAccount = CreditAccountQuery::create()
                ->filterByCustomerId($customer->getId())
                ->findOne();

            // Préparer les données pour le test
            $creditAmount = 50.00;
            $expirationDate = new \DateTime();
            $expirationDate->add(new \DateInterval('P30D'));
            
            if ($creditAccount) {
                $creditAmount = $creditAccount->getAmount();
                $calculatedExpiration = $this->getExpirationDate($creditAccount);
                if ($calculatedExpiration) {
                    $expirationDate = $calculatedExpiration;
                }
            }

            $message = MessageQuery::create()
                ->findOneByName(CreditReminder::CREDIT_REMINDER_MESSAGE_NAME);

            if (!$message) {
                return false;
            }

            // Préparer les données pour le template
            $parserContext = [
                'customer' => $customer,
                'credit_account' => $creditAccount,
                'credit_amount' => $creditAmount,
                'expiration_date' => $expirationDate,
                'store_name' => ConfigQuery::read('store_name'),
                'is_test' => true,
            ];

            // Récupérer la locale du client
            $locale = $customer->getCustomerLang() ? $customer->getCustomerLang()->getLocale() : 'fr_FR';

            // Envoyer l'email de test
            $this->mailerFactory->sendEmailMessage(
                $message->getName(),
                [ConfigQuery::read('store_email') => ConfigQuery::read('store_name')],
                [$email => 'Test'],
                $parserContext,
                $locale
            );

            return true;

        } catch (\Exception $e) {
            \Thelia\Log\Tlog::getInstance()->addError("Erreur lors de l'envoi de l'email de test : " . $e->getMessage());
            return false;
        }
    }
}
