<?php

namespace CreditReminder\Controller\Admin;

use CreditReminder\Model\CreditReminderQuery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Tools\URL;

class CreditReminderController extends BaseAdminController
{
    /**
     * @return mixed|string|RedirectResponse|Response|\Thelia\Core\HttpFoundation\Response|null
     */
    public function viewAction(): mixed
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['creditreminder'], AccessManager::VIEW)) {
            return $response;
        }

        // Get all reminders
        $reminders = CreditReminderQuery::create()
            ->orderByLastSentDate('DESC')
            ->find();

        return $this->render('credit-reminder', [
            'reminders' => $reminders,
            'reminderDaysBefore' => \CreditReminder\CreditReminder::getConfigValue('reminder_days_before'),
            'reminderIntervalDays' => \CreditReminder\CreditReminder::getConfigValue('reminder_interval_days'),
            'reminderMaxEmails' => \CreditReminder\CreditReminder::getConfigValue('reminder_max_emails')
        ]);
    }

    /**
     * @param Request $request
     * @return mixed|string|RedirectResponse|Response|\Thelia\Core\HttpFoundation\Response|null
     */
    #[Route('/admin/module/creditreminder/config/update', name: 'admin_creditreminder_config_update')]
    public function updateConfigAction(Request $request): mixed
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['creditreminder'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm('creditreminder.config.form');
        try {
            $form = $this->validateForm($form, "POST");
            $data = $form->getData();

            // Débogage
            error_log('Form data: ' . print_r($data, true));

            \CreditReminder\CreditReminder::setConfigValue('reminder_days_before', $data['reminder_days_before']);
            \CreditReminder\CreditReminder::setConfigValue('reminder_interval_days', $data['reminder_interval_days']);
            \CreditReminder\CreditReminder::setConfigValue('reminder_max_emails', $data['reminder_max_emails']);

            return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/CreditReminder'));

        } catch (\Exception $e) {
            error_log('Error: ' . $e->getMessage());
            $this->setupFormErrorContext(
                Translator::getInstance()->trans('Credit Reminder configuration error'),
                $e->getMessage(),
                $form
            );
            return $this->viewAction();
        }
    }
}
