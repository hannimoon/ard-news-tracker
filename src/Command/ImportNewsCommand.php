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
/**
 * Console command to import news from ARD API.
 *
 * @author Mandy Vorköper <mandy-vor@gmx.de>
 */
class ImportNewsCommand extends Command
{
    private NewsImporter $importer;

    /**
     * @param NewsImporter $importer The news importer service
     */
    public function __construct(NewsImporter $importer)
    {
        parent::__construct();
        $this->importer = $importer;
    }

    /**
     * Executes the news import command.
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     * @return int The command exit code
     */
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
