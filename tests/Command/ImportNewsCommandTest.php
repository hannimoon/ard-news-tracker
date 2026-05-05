<?php

namespace App\Tests\Command;

use App\Command\ImportNewsCommand;
use App\Service\NewsImporter;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unit tests for ImportNewsCommand.
 *
 * @author Mandy Vorköper <mandy-vor@gmx.de>
 */
class ImportNewsCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private NewsImporter $newsImporter;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        // Mocking the NewsImporter service
        $this->newsImporter = $this->createMock(NewsImporter::class);

        $command = new ImportNewsCommand($this->newsImporter);
        $application->addCommand($command);

        $this->commandTester = new CommandTester($application->find('app:import-news'));
    }

    /**
     * Test that command successfully imports news and displays correct output.
     */
    public function testCommandSuccessfullyImportsNews(): void
    {
        $this->newsImporter
            ->expects($this->once())
            ->method('import')
            ->willReturn(5);

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('5 Nachrichten wurden erfolgreich importiert', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Starte ARD News Import', $this->commandTester->getDisplay());
    }

    /**
     * Test that command handles import exceptions and returns failure status.
     */
    public function testCommandHandlesImportException(): void
    {
        $this->newsImporter
            ->expects($this->once())
            ->method('import')
            ->willThrowException(new \Exception('API connection failed'));

        $this->commandTester->execute([]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Fehler beim Import: API connection failed', $this->commandTester->getDisplay());
    }
}

