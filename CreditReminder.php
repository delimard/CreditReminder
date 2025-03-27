<?php
namespace CreditReminder;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Thelia\Install\Database;
use Thelia\Model\MessageQuery;
use Thelia\Model\Message;
use Thelia\Module\BaseModule;

class CreditReminder extends BaseModule
{
    /** @var string */
    public const DOMAIN_NAME = 'creditreminder';
    public const CREDIT_REMINDER_MESSAGE_NAME = 'credit_reminder';

    public const REMINDER_DAYS_BEFORE = 'first_reminder_in_days';
    public const REMINDER_INTERVAL_DAYS = 'interval_reminder_in_days';
    public const REMINDER_MAX_EMAILS = 'max_email_reminder';

    /** @const Event constant for sending credit reminder emails */
    public const CREDIT_REMINDER_SEND_EMAIL = 'credit_reminder.send_email';

    /**
     * @param ConnectionInterface|null $con
     * @return void
     * @throws PropelException
     */
    public function postActivation(ConnectionInterface $con = null): void
    {
        try {
            $this->setConfigVariables();
            
            // Ensure SQL schema is correctly installed
            $database = new Database($con);
            $database->insertSql(null, [__DIR__ . "/Config/thelia.sql"]);
            
            // Create credit reminder message if it doesn't exist
            if (null === MessageQuery::create()->findOneByName(self::CREDIT_REMINDER_MESSAGE_NAME)) {
                $message = new Message();
                $email_templates_dir = __DIR__ . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'backOffice' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'email' . DIRECTORY_SEPARATOR;

                $message
                    ->setName(self::CREDIT_REMINDER_MESSAGE_NAME)
                    ->setLocale('en_US')
                    ->setTitle('Credit Reminder')
                    ->setSubject('Credit Reminder for your Account')
                    ->setHtmlMessage(file_get_contents($email_templates_dir . 'credit-reminder.html'))
                    ->setTextMessage(file_get_contents($email_templates_dir . 'credit-reminder.txt'))

                    ->setLocale('fr_FR')
                    ->setTitle('Rappel de Crédit')
                    ->setSubject('Rappel de Crédit pour votre Compte')
                    ->setHtmlMessage(file_get_contents($email_templates_dir . 'credit-reminder.html'))
                    ->setTextMessage(file_get_contents($email_templates_dir . 'credit-reminder.txt'))

                    ->save()
                ;
            }
        } catch (\Exception $e) {
            // Log or handle the exception as needed
        }
    }

    /**
     * Set default configuration variables
     */
    private function setConfigVariables(): void
    {
        if (null === self::getConfigValue(self::REMINDER_DAYS_BEFORE, null)) {
            self::setConfigValue(self::REMINDER_DAYS_BEFORE, 30);
        }
        if (null === self::getConfigValue(self::REMINDER_INTERVAL_DAYS, null)) {
            self::setConfigValue(self::REMINDER_INTERVAL_DAYS, 10);
        }
        if (null === self::getConfigValue(self::REMINDER_MAX_EMAILS, null)) {
            self::setConfigValue(self::REMINDER_MAX_EMAILS, 2);
        }
    }

    // Reste du code du module inchangé

   /**
     * @param ServicesConfigurator $servicesConfigurator
     * @return void
     */
    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()). "/I18n/*"])
            ->autowire(true)
            ->autoconfigure(true);
    }
}