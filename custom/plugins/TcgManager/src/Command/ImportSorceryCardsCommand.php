<?php declare(strict_types=1);

namespace TcgManager\Command;

use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TcgManager\Service\SorceryApiImportService;

#[AsCommand(
    name: 'tcg:import:sorcery',
    description: 'Import cards from Sorcery: Contested Realm API'
)]
class ImportSorceryCardsCommand extends Command
{
    private SorceryApiImportService $importService;

    public function __construct(SorceryApiImportService $importService)
    {
        parent::__construct();
        $this->importService = $importService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Import cards from Sorcery: Contested Realm API')
            ->setHelp('This command imports all available cards from the Sorcery TCG API into the database.')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force import even if cards already exist'
            )
            ->addOption(
                'stats-only',
                's',
                InputOption::VALUE_NONE,
                'Show import statistics only, do not import'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $io->title('🎮 Sorcery: Contested Realm Card Import');

        // Show statistics if requested
        if ($input->getOption('stats-only')) {
            $this->showStats($io, $context);
            return Command::SUCCESS;
        }

        // Confirm import
        if (!$input->getOption('force')) {
            if (!$io->confirm('This will import/update cards from the Sorcery API. Continue?', false)) {
                $io->info('Import cancelled.');
                return Command::SUCCESS;
            }
        }

        // Start import
        $io->section('Starting import...');
        
        try {
            $stats = $this->importService->importAllCards($context);
            
            $io->success('Import completed successfully!');
            
            // Display results
            $io->table(
                ['Metric', 'Count'],
                [
                    ['Total cards processed', $stats['total']],
                    ['New cards imported', $stats['imported']],
                    ['Existing cards updated', $stats['updated']],
                    ['Cards skipped', $stats['skipped']],
                    ['Errors encountered', $stats['errors']],
                ]
            );

            if ($stats['errors'] > 0) {
                $io->warning("Import completed with {$stats['errors']} errors. Check the logs for details.");
                return Command::FAILURE;
            }

            // Show final statistics
            $this->showStats($io, $context);

            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function showStats(SymfonyStyle $io, Context $context): void
    {
        $io->section('📊 Current Database Statistics');
        
        try {
            $stats = $this->importService->getImportStats($context);
            
            $io->table(
                ['Statistic', 'Value'],
                [
                    ['Total Sorcery cards in database', $stats['total_sorcery_cards']],
                    ['Last import date', $stats['last_import'] ? $stats['last_import']->format('Y-m-d H:i:s') : 'Never'],
                ]
            );
            
        } catch (\Exception $e) {
            $io->error('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}
