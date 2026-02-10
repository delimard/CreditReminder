<?php

namespace CreditReminder\Form;

use CreditReminder\CreditReminder;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Form\BaseForm;

class ConfigurationForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'reminder_days_before',
                IntegerType::class,
                [
                    'label' => $this->translator->trans('Number of days before expiration', [], CreditReminder::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'reminder_days_before',
                        'help' => $this->translator->trans(
                            'Number of days before credit expiration to send the reminder email',
                            [],
                            CreditReminder::DOMAIN_NAME
                        )
                    ],
                    'required' => false,
                    'constraints' => [
                        new GreaterThan(['value' => 0])
                    ],
                    'data' => CreditReminder::getConfigValue(CreditReminder::REMINDER_DAYS_BEFORE, 30),
                ]
            )
            ->add(
                'reminder_interval_days',
                IntegerType::class,
                [
                    'label' => $this->translator->trans('Reminder interval (days)', [], CreditReminder::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'reminder_interval_days',
                        'help' => $this->translator->trans(
                            'Minimum number of days between two reminder emails to the same customer',
                            [],
                            CreditReminder::DOMAIN_NAME
                        )
                    ],
                    'required' => false,
                    'constraints' => [
                        new GreaterThan(['value' => 0])
                    ],
                    'data' => CreditReminder::getConfigValue(CreditReminder::REMINDER_INTERVAL_DAYS, 10),
                ]
            )
            ->add(
                'max_emails_per_run',
                IntegerType::class,
                [
                    'label' => $this->translator->trans('Nombre maximum d\'emails par exécution', [], CreditReminder::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'max_emails_per_run',
                        'help' => $this->translator->trans(
                            'Limite le nombre d\'emails envoyés à chaque exécution pour éviter la surcharge du serveur',
                            [],
                            CreditReminder::DOMAIN_NAME
                        )
                    ],
                    'required' => false,
                    'constraints' => [
                        new GreaterThan(['value' => 0])
                    ],
                    'data' => CreditReminder::getConfigValue(CreditReminder::REMINDER_MAX_EMAILS, 100),
                ]
            )
            ->add(
                'test_email',
                EmailType::class,
                [
                    'label' => $this->translator->trans('Email de test', [], CreditReminder::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'test_email',
                        'help' => $this->translator->trans(
                            'Adresse email pour recevoir un email de test',
                            [],
                            CreditReminder::DOMAIN_NAME
                        )
                    ],
                    'required' => false,
                    'constraints' => [
                        new Email()
                    ],
                ]
            )
            ->add(
                'test_customer_id',
                IntegerType::class,
                [
                    'label' => $this->translator->trans('ID de client (optionnel)', [], CreditReminder::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'test_customer_id',
                        'help' => $this->translator->trans(
                            'ID d\'un client spécifique à utiliser pour le test (sinon utilise un client avec crédit)',
                            [],
                            CreditReminder::DOMAIN_NAME
                        )
                    ],
                    'required' => false,
                    'constraints' => [
                        new GreaterThan(['value' => 0])
                    ],
                ]
            );
    }

    public static function getName()
    {
        return 'creditreminder_configuration';
    }
}
