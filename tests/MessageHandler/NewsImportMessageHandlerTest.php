<?php

namespace App\Tests\MessageHandler;

use App\Message\NewsImportMessage;
use App\MessageHandler\NewsImportMessageHandler;
use App\Service\NewsImporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

/**
 * Unit tests for NewsImportMessageHandler.
 *
 * @author Mandy Vorköper <mandy-vor@gmx.de>
 */
class NewsImportMessageHandlerTest extends TestCase
{
    private NewsImporter $newsImporter;
    private LockFactory $lockFactory;
    private LoggerInterface $logger;
    private NewsImportMessageHandler $handler;

    protected function setUp(): void
    {
        $this->newsImporter = $this->createMock(NewsImporter::class);
        $this->lockFactory = $this->createMock(LockFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new NewsImportMessageHandler($this->newsImporter, $this->lockFactory, $this->logger);
    }

    /**
     * Test that handler invokes import when lock is acquired.
     */
    public function testInvokeWhenLockAcquired(): void
    {
        $lock = $this->createMock(SharedLockInterface::class);
        $this->lockFactory->expects($this->once())
            ->method('createLock')
            ->with('global_news_import')
            ->willReturn($lock);

        $lock->expects($this->once())
            ->method('acquire')
            ->willReturn(true);

        $this->newsImporter->expects($this->once())
            ->method('import');

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->with($this->callback(function($msg) {
                return in_array($msg, ['News-Import wird gestartet...', 'News-Import abgeschlossen.']);
            }));

        $lock->expects($this->once())
            ->method('release');

        $message = new NewsImportMessage();
        $this->handler->__invoke($message);
    }

    /**
     * Test that handler skips import when lock is not acquired.
     */
    public function testInvokeWhenLockNotAcquired(): void
    {
        $lock = $this->createMock(SharedLockInterface::class);
        $this->lockFactory->expects($this->once())
            ->method('createLock')
            ->with('global_news_import')
            ->willReturn($lock);

        $lock->expects($this->once())
            ->method('acquire')
            ->willReturn(false);

        $this->newsImporter->expects($this->never())
            ->method('import');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('News-Import abgebrochen: Ein anderer Import-Prozess läuft noch.');

        $lock->expects($this->never())
            ->method('release');

        $message = new NewsImportMessage();
        $this->handler->__invoke($message);
    }

    /**
     * Test that handler logs error and releases lock when import throws exception.
     */
    public function testInvokeWhenImportThrowsException(): void
    {
        $lock = $this->createMock(SharedLockInterface::class);
        $this->lockFactory->expects($this->once())
            ->method('createLock')
            ->with('global_news_import')
            ->willReturn($lock);

        $lock->expects($this->once())
            ->method('acquire')
            ->willReturn(true);

        $this->newsImporter->expects($this->once())
            ->method('import')
            ->willThrowException(new \Exception('Import failed'));

        $this->logger->expects($this->once())
            ->method('info')
            ->with('News-Import wird gestartet...');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Fehler während des News-Imports: Import failed');

        $lock->expects($this->once())
            ->method('release');

        $message = new NewsImportMessage();
        $this->handler->__invoke($message);
    }
}