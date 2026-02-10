<?php

namespace CreditReminder\Controller\Admin;

use CreditReminder\CreditReminder;
use CreditReminder\Form\ConfigurationForm;
use CreditReminder\Model\CreditReminderLogQuery;
use CreditReminder\Service\CreditReminderService;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;

class CreditReminderController extends BaseAdminController
{
    public function viewAction()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['CreditReminder'], AccessManager::VIEW)) {
            return $response;
        }

        return $this->render('credit-reminder', [
            'reminder_days_before' => CreditReminder::getConfigValue(CreditReminder::REMINDER_DAYS_BEFORE, 30),
            'reminder_interval_days' => CreditReminder::getConfigValue(CreditReminder::REMINDER_INTERVAL_DAYS, 10),
            'max_emails_per_run' => CreditReminder::getConfigValue(CreditReminder::REMINDER_MAX_EMAILS, 100),
        ]);
    }

    #[Route('/admin/module/CreditReminder/config/update', name: 'admin_creditreminder_config_update')]
    public function updateConfigAction(Request $request)
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['CreditReminder'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm('creditreminder.configuration');
        
        try {
            $form = $this->validateForm($form, "POST");
            $data = $form->getData();

            // Sauvegarder la configuration
            if (isset($data['reminder_days_before'])) {
                CreditReminder::setConfigValue(CreditReminder::REMINDER_DAYS_BEFORE, $data['reminder_days_before']);
            }
            
            if (isset($data['reminder_interval_days'])) {
                CreditReminder::setConfigValue(CreditReminder::REMINDER_INTERVAL_DAYS, $data['reminder_interval_days']);
            }
            
            if (isset($data['max_emails_per_run'])) {
                CreditReminder::setConfigValue(CreditReminder::REMINDER_MAX_EMAILS, $data['max_emails_per_run']);
            }

            $this->adminLogAppend(
                "creditreminder.configuration.message",
                AccessManager::UPDATE,
                "CreditReminder configuration updated"
            );

            return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/CreditReminder'));

        } catch (FormValidationException $ex) {
            $errorMessage = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            $errorMessage = $ex->getMessage();
        }

        $this->setupFormErrorContext(
            Translator::getInstance()->trans("Configuration du module", [], CreditReminder::DOMAIN_NAME),
            $errorMessage,
            $form,
            $ex
        );

        return $this->viewAction();
    }

    #[Route('/admin/module/CreditReminder/send-test', name: 'admin_creditreminder_send_test')]
    public function sendTestEmailAction(Request $request, CreditReminderService $creditReminderService)
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['CreditReminder'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm('creditreminder.configuration');

        try {
            $form = $this->validateForm($form, "POST");
            $testEmail = $form->get('test_email')->getData();
            $testCustomerId = $form->get('test_customer_id')->getData();

            if (empty($testEmail)) {
                throw new \Exception(
                    Translator::getInstance()->trans(
                        "Veuillez saisir une adresse email",
                        [],
                        CreditReminder::DOMAIN_NAME
                    )
                );
            }

            // Envoyer l'email de test
            if ($creditReminderService->sendTestEmail($testEmail, $testCustomerId)) {
                $this->getParserContext()->set('success_message', 
                    Translator::getInstance()->trans(
                        "L'email de test a été envoyé avec succès à %email",
                        ['%email' => $testEmail],
                        CreditReminder::DOMAIN_NAME
                    )
                );
            } else {
                throw new \Exception(
                    Translator::getInstance()->trans(
                        "Erreur lors de l'envoi de l'email de test",
                        [],
                        CreditReminder::DOMAIN_NAME
                    )
                );
            }

        } catch (FormValidationException $ex) {
            $errorMessage = $this->createStandardFormValidationErrorMessage($ex);
            $this->setupFormErrorContext(
                Translator::getInstance()->trans("Envoi d'email de test", [], CreditReminder::DOMAIN_NAME),
                $errorMessage,
                $form,
                $ex
            );
        } catch (\Exception $ex) {
            $this->getParserContext()->set('error_message', $ex->getMessage());
        }

        return $this->viewAction();
    }
}
