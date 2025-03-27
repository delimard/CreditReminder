<?php

namespace CreditReminder\Form;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints;
use Thelia\Form\BaseForm;

class ConfigurationForm extends BaseForm
{
    /**
     * @return void
     */
    protected function buildForm(): void
    {
        $this->formBuilder
            ->add('reminder_days_before', IntegerType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                    new Constraints\Range(['min' => 1, 'max' => 90])
                ],
                'label' => $this->translator->trans('Days before expiration to send reminder', [], \CreditReminder\CreditReminder::DOMAIN_NAME),
                'data' => \CreditReminder\CreditReminder::getConfigValue('reminder_days_before', 30)
            ])
            ->add('reminder_interval_days', IntegerType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                    new Constraints\Range(['min' => 1, 'max' => 30])
                ],
                'label' => $this->translator->trans('Interval between reminder emails (in days)', [], \CreditReminder\CreditReminder::DOMAIN_NAME),
                'data' => \CreditReminder\CreditReminder::getConfigValue('reminder_interval_days', 10)
            ])
            ->add('reminder_max_emails', IntegerType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                    new Constraints\Range(['min' => 1, 'max' => 5])
                ],
                'label' => $this->translator->trans('Maximum number of reminder emails to send', [], \CreditReminder\CreditReminder::DOMAIN_NAME),
                'data' => \CreditReminder\CreditReminder::getConfigValue('reminder_max_emails', 2)
            ]);
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'creditreminder_config_form';
    }
}
