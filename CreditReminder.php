<?php

namespace CreditReminder;

use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Thelia\Core\Translation\Translator;
use Thelia\Install\Database;
use Thelia\Model\Lang;
use Thelia\Model\LangQuery;
use Thelia\Model\Message;
use Thelia\Model\MessageQuery;
use Thelia\Module\BaseModule;

class CreditReminder extends BaseModule
{
    /** @var string */
    public const DOMAIN_NAME = 'creditreminder';
    public const CREDIT_REMINDER_MESSAGE_NAME = 'credit-reminder-message';
    
    public const REMINDER_DAYS_BEFORE = 'reminder_days_before';
    public const REMINDER_INTERVAL_DAYS = 'reminder_interval_days';
    public const REMINDER_MAX_EMAILS = 'max_emails_per_run';

    /**
     * @param ConnectionInterface|null $con
     * @return void
     * @throws PropelException
     */
    public function postActivation(ConnectionInterface $con = null): void
    {
        if (null === self::getConfigValue('is-initialized')) {
            $database = new Database($con);
            $database->insertSql(null, [__DIR__ . "/Config/thelia.sql"]);

            self::setConfigValue('is-initialized', 1);
        }

        // Valeur par défaut : 30 jours avant expiration
        if (null === self::getConfigValue(self::REMINDER_DAYS_BEFORE)) {
            self::setConfigValue(self::REMINDER_DAYS_BEFORE, 30);
        }

        // Valeur par défaut : 10 jours entre deux rappels
        if (null === self::getConfigValue(self::REMINDER_INTERVAL_DAYS)) {
            self::setConfigValue(self::REMINDER_INTERVAL_DAYS, 10);
        }

        // Valeur par défaut : 100 emails par exécution
        if (null === self::getConfigValue(self::REMINDER_MAX_EMAILS)) {
            self::setConfigValue(self::REMINDER_MAX_EMAILS, 100);
        }

        // Créer le message email si il n'existe pas
        if (null === MessageQuery::create()->findOneByName(self::CREDIT_REMINDER_MESSAGE_NAME)) {

            $message = new Message();
            $message
                ->setName(self::CREDIT_REMINDER_MESSAGE_NAME)
                ->setHtmlLayoutFileName('')
                ->setHtmlTemplateFileName('credit-reminder.html')
                ->setTextLayoutFileName('')
                ->setTextTemplateFileName('credit-reminder.txt');

            $languages = LangQuery::create()->find();

            foreach ($languages as $language) {
                /** @var Lang $language */
                $locale = $language->getLocale();

                $message->setLocale($locale);

                $message->setTitle(
                    Translator::getInstance()->trans("Your loyalty credit is about to expire!", [], self::DOMAIN_NAME, $locale)
                );

                $message->setSubject(
                    Translator::getInstance()->trans("Your loyalty credit is about to expire!", [], self::DOMAIN_NAME, $locale)
                );
            }

            $message->save();
        }
    }

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
