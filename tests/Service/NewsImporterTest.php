<?php

namespace App\Tests\Service;

use App\Entity\News;
use App\Repository\NewsRepository;
use App\Service\NewsImporter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class NewsImporterTest extends TestCase
{
    private NewsImporter $newsImporter;
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;
    private NewsRepository $newsRepository;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->newsRepository = $this->createMock(NewsRepository::class);

        $this->newsImporter = new NewsImporter(
            $this->httpClient,
            $this->entityManager,
            $this->newsRepository
        );
    }

    public function testImportSuccessfullyImportsNews(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'news' => [
                [
                    'externalId' => 'news-1',
                    'title' => 'Test News',
                    'firstSentence' => 'This is a test news',
                    'date' => '2026-03-27T10:00:00',
                    'shareurl' => 'https://example.com/news-1',
                    'type' => 'article'
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://tagesschau.de/api2u/news/?regions=8&ressort=inland')
            ->willReturn($mockResponse);

        $this->newsRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['externalId' => 'news-1'])
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->newsImporter->import();

        $this->assertEquals(1, $result);
    }

    public function testImportSkipsDuplicateNews(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'news' => [
                [
                    'externalId' => 'news-1',
                    'title' => 'Test News',
                    'firstSentence' => 'This is a test news',
                    'date' => '2026-03-27T10:00:00',
                    'shareurl' => 'https://example.com/news-1',
                    'type' => 'article'
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $existingNews = new News();
        $this->newsRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['externalId' => 'news-1'])
            ->willReturn($existingNews);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->newsImporter->import();

        $this->assertEquals(0, $result);
    }

    public function testImportSkipsNewsWithoutExternalId(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'news' => [
                [
                    'title' => 'Test News',
                    'firstSentence' => 'This is a test news',
                    'date' => '2026-03-27T10:00:00',
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->newsRepository
            ->expects($this->never())
            ->method('findOneBy');

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->newsImporter->import();

        $this->assertEquals(0, $result);
    }

    public function testImportHandlesApiError(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(500);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API Fehler: Status 500');

        $this->newsImporter->import();
    }

    public function testImportHandles403ApiError(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(403);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API Fehler: Status 403');

        $this->newsImporter->import();
    }

    public function testImportMultipleNews(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'news' => [
                [
                    'externalId' => 'news-1',
                    'title' => 'First News',
                    'firstSentence' => 'First news content',
                    'date' => '2026-03-27T10:00:00',
                    'shareurl' => 'https://example.com/news-1',
                    'type' => 'article'
                ],
                [
                    'externalId' => 'news-2',
                    'title' => 'Second News',
                    'firstSentence' => 'Second news content',
                    'date' => '2026-03-27T11:00:00',
                    'shareurl' => 'https://example.com/news-2',
                    'type' => 'article'
                ],
                [
                    'externalId' => 'news-3',
                    'title' => 'Third News',
                    'firstSentence' => 'Third news content',
                    'date' => '2026-03-27T12:00:00',
                    'shareurl' => 'https://example.com/news-3',
                    'type' => 'article'
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->newsRepository
            ->expects($this->exactly(3))
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager
            ->expects($this->exactly(3))
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->newsImporter->import();

        $this->assertEquals(3, $result);
    }

    public function testSentimentAnalysisForBadNews(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'news' => [
                [
                    'externalId' => 'news-1',
                    'title' => 'Krise in der Wirtschaft',
                    'firstSentence' => 'Die Wirtschaft befindet sich in einer Krise',
                    'date' => '2026-03-27T10:00:00',
                    'shareurl' => 'https://example.com/news-1',
                    'type' => 'article'
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->newsRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $persistedNews = null;
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function($news) use (&$persistedNews) {
                $persistedNews = $news;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->newsImporter->import();

        $this->assertNotNull($persistedNews);
        $this->assertEquals('bad', $persistedNews->getSentiment());
    }

    public function testSentimentAnalysisForGoodNews(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'news' => [
                [
                    'externalId' => 'news-1',
                    'title' => 'Großer Erfolg für das Unternehmen',
                    'firstSentence' => 'Das Unternehmen hat einen Rekord erreicht',
                    'date' => '2026-03-27T10:00:00',
                    'shareurl' => 'https://example.com/news-1',
                    'type' => 'article'
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->newsRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $persistedNews = null;
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function($news) use (&$persistedNews) {
                $persistedNews = $news;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->newsImporter->import();

        $this->assertNotNull($persistedNews);
        $this->assertEquals('good', $persistedNews->getSentiment());
    }

    public function testSentimentAnalysisForNeutralNews(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'news' => [
                [
                    'externalId' => 'news-1',
                    'title' => 'Normales Ereignis',
                    'firstSentence' => 'Ein normales Ereignis hat stattgefunden',
                    'date' => '2026-03-27T10:00:00',
                    'shareurl' => 'https://example.com/news-1',
                    'type' => 'article'
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->newsRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $persistedNews = null;
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function($news) use (&$persistedNews) {
                $persistedNews = $news;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->newsImporter->import();

        $this->assertNotNull($persistedNews);
        $this->assertEquals('neutral', $persistedNews->getSentiment());
    }

    public function testImportHandlesHttpClientException(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Network error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Network error');

        $this->newsImporter->import();
    }

    public function testImportHandlesMissingOptionalFields(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'news' => [
                [
                    'externalId' => 'news-1',
                    // Missing title, firstSentence, date, shareurl, type
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->newsRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $persistedNews = null;
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function($news) use (&$persistedNews) {
                $persistedNews = $news;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->newsImporter->import();

        $this->assertEquals(1, $result);
        $this->assertNotNull($persistedNews);
        $this->assertEquals('Kein Titel', $persistedNews->getTitle());
        $this->assertEquals('', $persistedNews->getContent());
        $this->assertEquals('none', $persistedNews->getType());
    }

    public function testImportUsesShareurlAsFallbackForExternalId(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'news' => [
                [
                    'shareurl' => 'https://example.com/news-1',
                    'title' => 'Test News',
                    'firstSentence' => 'Test content',
                    'date' => '2026-03-27T10:00:00',
                    'type' => 'article'
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->newsRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['externalId' => 'https://example.com/news-1'])
            ->willReturn(null);

        $persistedNews = null;
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function($news) use (&$persistedNews) {
                $persistedNews = $news;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->newsImporter->import();

        $this->assertEquals(1, $result);
        $this->assertNotNull($persistedNews);
        $this->assertEquals('https://example.com/news-1', $persistedNews->getExternalId());
    }

    public function testImportWithEmptyNewsArray(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'news' => []
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->newsImporter->import();

        $this->assertEquals(0, $result);
    }
}

