<?php

namespace App\Command;

use App\Repository\DemoRunRepository;
use App\Repository\LeadRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsCommand(name: 'app:retention:purge', description: 'Purge old leads and demo runs')]
class RetentionPurgeCommand extends Command
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly DemoRunRepository $demoRunRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('lead-days', null, InputOption::VALUE_REQUIRED, 'Days before leads are purged', '90')
            ->addOption('demo-days', null, InputOption::VALUE_REQUIRED, 'Days before demo runs are purged', '30');
    }

    /**
     * Registered as a scheduler task: runs daily at 03:00 (consumed by the
     * opt-in scheduler worker on the "scheduler_default" transport).
     *
     * @AsPeriodicTask is autoconfigured into the "scheduler.task" service tag.
     */
    #[AsPeriodicTask(frequency: 'every day', from: '03:00')]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $leadDays = (int) $input->getOption('lead-days');
        $demoDays = (int) $input->getOption('demo-days');

        $now = new \DateTimeImmutable();

        $leadsPurged = $this->leadRepository->purgeExpired((clone $now)->modify('-' . $leadDays . ' days'));
        $output->writeln(sprintf('Purged %d leads older than %d days', $leadsPurged, $leadDays));

        $runsPurged = $this->demoRunRepository->purgeOlderThan((clone $now)->modify('-' . $demoDays . ' days'));
        $output->writeln(sprintf('Purged %d demo runs older than %d days', $runsPurged, $demoDays));

        return Command::SUCCESS;
    }
}
