<?php
namespace App\Command;

use App\Service\NewsImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-news',
    description: 'Importiert tagesaktuelle News von der ARD API',
)]
class ImportNewsCommand extends Command
{
    private NewsImporter $importer;

    public function __construct(NewsImporter $importer)
    {
        parent::__construct();
        $this->importer = $importer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Starte ARD News Import');

        try {

            $count = $this->importer->import();

            $io->success($count . ' Nachrichten wurden erfolgreich importiert.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Fehler beim Import: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
