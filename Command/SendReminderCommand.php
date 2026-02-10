<?php

namespace CreditReminder\Command;

use CreditReminder\CreditReminder;
use CreditReminder\Service\CreditReminderService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Thelia\Command\ContainerAwareCommand;

class SendReminderCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'CreditReminder:send';

    protected $creditReminderService;

    public function __construct(CreditReminderService $creditReminderService)
    {
        parent::__construct();
        $this->creditReminderService = $creditReminderService;
    }

    protected function configure()
    {
        $this
            ->setName('CreditReminder:send')
            ->setDescription('Envoie les emails de rappel de crédit')
            ->setHelp('Cette commande envoie des emails de rappel aux clients dont le crédit expire bientôt')
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Nombre de jours avant expiration (utilise la configuration par défaut si non spécifié)'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Mode test : affiche les clients éligibles sans envoyer d\'emails'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Initialiser la requête pour avoir accès à la session
        $this->initRequest();
        
        $io = new SymfonyStyle($input, $output);

        // Récupérer le nombre de jours depuis la configuration ou l'option
        $days = $input->getOption('days');
        if (!$days) {
            $days = CreditReminder::getConfigValue(CreditReminder::REMINDER_DAYS_BEFORE, 30);
        }

        // Récupérer la limite d'emails par exécution
        $maxEmails = CreditReminder::getConfigValue(CreditReminder::REMINDER_MAX_EMAILS, 100);

        $dryRun = $input->getOption('dry-run');

        $io->title('Envoi des rappels de crédit');
        $io->text("Recherche des clients avec crédit expirant dans {$days} jours ou moins...");
        $io->text("Limite d'envoi : {$maxEmails} emails par exécution");

        // Récupérer les clients éligibles avec limite
        $eligibleCustomers = $this->creditReminderService->getEligibleCustomers($days, $maxEmails);

        if (empty($eligibleCustomers)) {
            $io->success('Aucun client éligible trouvé.');
            return Command::SUCCESS;
        }

        $io->text(sprintf('Nombre de clients éligibles : %d', count($eligibleCustomers)));

        if ($dryRun) {
            $io->warning('Mode DRY-RUN activé : aucun email ne sera envoyé');
            
            $tableData = [];
            foreach ($eligibleCustomers as $data) {
                $customer = $data['customer'];
                $creditAccount = $data['creditAccount'];
                $expirationDate = $data['expirationDate'];
                $tableData[] = [
                    $customer->getId(),
                    $customer->getEmail(),
                    number_format($creditAccount->getAmount(), 2) . ' €',
                    $expirationDate->format('Y-m-d'),
                ];
            }

            $io->table(
                ['ID Client', 'Email', 'Montant crédit', 'Date d\'expiration'],
                $tableData
            );

            return Command::SUCCESS;
        }

        // Envoyer les emails
        $io->progressStart(count($eligibleCustomers));
        
        $successCount = 0;
        $errorCount = 0;

        foreach ($eligibleCustomers as $data) {
            $customer = $data['customer'];
            $creditAccount = $data['creditAccount'];
            $expirationDate = $data['expirationDate'];
            
            if ($this->creditReminderService->sendReminderEmail($customer, $creditAccount, $expirationDate, false)) {
                $successCount++;
            } else {
                $errorCount++;
            }
            $io->progressAdvance();
        }

        $io->progressFinish();

        // Afficher le résumé
        $io->success(sprintf(
            'Envoi terminé : %d emails envoyés avec succès, %d erreurs',
            $successCount,
            $errorCount
        ));

        return Command::SUCCESS;
    }
}
