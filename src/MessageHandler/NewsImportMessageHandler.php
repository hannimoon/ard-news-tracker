<?php
namespace App\MessageHandler;

use App\Message\NewsImportMessage;
use App\Service\NewsImporter;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Lock\LockFactory;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class NewsImportMessageHandler
{
    public function __construct(
        private NewsImporter $newsImport,
        private LockFactory $lockFactory,
        private LoggerInterface $logger
    ) {}

    public function __invoke(NewsImportMessage $message)
    {
        // 1. Sicherheit: Globales Lock, damit der Import nicht doppelt läuft
        $lock = $this->lockFactory->createLock('global_news_import');

        if (!$lock->acquire()) {
            $this->logger->info('News-Import abgebrochen: Ein anderer Import-Prozess läuft noch.');
            return;
        }

        try {
            $this->logger->info('News-Import wird gestartet...');

            $this->newsImport->import();
          
            $this->logger->info('News-Import abgeschlossen.');

        } catch (\Exception $e) {
            $this->logger->error('Fehler während des News-Imports: ' . $e->getMessage());
            // Optional: Exception neu werfen, damit der Messenger einen Retry macht
            // throw $e; 
        } finally {
            // 4. Lock auf jeden Fall wieder freigeben
            $lock->release();
        }
    }
}
