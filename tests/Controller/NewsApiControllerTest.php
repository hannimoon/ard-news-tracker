<?php

namespace App\Tests\Controller;

use App\Controller\NewsApiController;
use App\Repository\NewsRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Unit tests for NewsApiController.
 *
 * @author Mandy Vorköper <mandy-vor@gmx.de>
 */
class NewsApiControllerTest extends TestCase
{
    private NewsApiController $controller;
    private NewsRepository $newsRepository;

    protected function setUp(): void
    {
        $this->controller = new NewsApiController();
        $this->newsRepository = $this->createMock(NewsRepository::class);
    }

    public function testGetNewsReturnsJsonResponseWithFiltersAndPagination(): void
    {
        $expectedData = [
            'data' => [['id' => 1, 'title' => 'Test']],
            'meta' => ['total' => 1, 'page' => 2, 'limit' => 5, 'pages' => 1],
        ];

        $this->newsRepository
            ->expects($this->once())
            ->method('findFilteredNews')
            ->with('DE', '2026-05-12', 'good', 'webview', 2, 5)
            ->willReturn($expectedData);

        $request = Request::create('/api/news', 'GET', [
            'region' => 'DE',
            'date' => '2026-05-12',
            'sentiment' => 'good',
            'type' => 'webview',
            'page' => '2',
            'limit' => '5',
        ]);

        $response = $this->controller->getNews($request, $this->newsRepository);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedData, json_decode($response->getContent(), true));
    }

    public function testGetNewsUsesDefaultValuesWhenParametersAreMissing(): void
    {
        $expectedData = [
            'data' => [],
            'meta' => ['total' => 0, 'page' => 1, 'limit' => 10, 'pages' => 0],
        ];

        $this->newsRepository
            ->expects($this->once())
            ->method('findFilteredNews')
            ->with(null, null, null, 'story', 1, 10)
            ->willReturn($expectedData);

        $request = Request::create('/api/news', 'GET', []);

        $response = $this->controller->getNews($request, $this->newsRepository);

        $this->assertSame($expectedData, json_decode($response->getContent(), true));
    }

    public function testGetNewsNormalizesEmptyStringAndInvalidPaginationValues(): void
    {
        $expectedData = [
            'data' => [],
            'meta' => ['total' => 0, 'page' => 1, 'limit' => 10, 'pages' => 0],
        ];

        $this->newsRepository
            ->expects($this->once())
            ->method('findFilteredNews')
            ->with(null, null, null, 'story', 1, 10)
            ->willReturn($expectedData);

        $request = Request::create('/api/news', 'GET', [
            'region' => '',
            'date' => '',
            'sentiment' => '',
            'type' => '',
            'page' => '0',
            'limit' => '-5',
        ]);

        $response = $this->controller->getNews($request, $this->newsRepository);

        $this->assertSame($expectedData, json_decode($response->getContent(), true));
    }

    public function testGetNewsReturnsBadRequestOnInvalidType(): void
    {
        $this->newsRepository
            ->expects($this->never())
            ->method('findFilteredNews');

        $request = Request::create('/api/news', 'GET', [
            'type' => 'article',
        ]);

        $response = $this->controller->getNews($request, $this->newsRepository);
       
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            ['error' => 'Invalid type. Allowed values are webview or story.'],
            json_decode($response->getContent(), true)
        );
    }

    public function testGetNewsReturnsBadRequestForInvalidSentiment(): void
    {
        $this->newsRepository
            ->expects($this->never())
            ->method('findFilteredNews');

        $request = Request::create('/api/news', 'GET', [
            'sentiment' => 'awesome',
        ]);

        $response = $this->controller->getNews($request, $this->newsRepository);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            ['error' => 'Invalid sentiment. Allowed values are good, bad, or neutral.'],
            json_decode($response->getContent(), true)
        );
    }

    public function testGetNewsCapsLimitAtMaximumValue(): void
    {
        $expectedData = [
            'data' => [],
            'meta' => ['total' => 0, 'page' => 1, 'limit' => 1000, 'pages' => 0],
        ];

        $this->newsRepository
            ->expects($this->once())
            ->method('findFilteredNews')
            ->with(null, null, null, 'story', 1, 1000)
            ->willReturn($expectedData);

        $request = Request::create('/api/news', 'GET', [
            'limit' => '2000',
        ]);

        $response = $this->controller->getNews($request, $this->newsRepository);

        $this->assertSame($expectedData, json_decode($response->getContent(), true));
    }

    public function testGetNewsReturnsBadRequestForInvalidDateFormat(): void
    {
        $this->newsRepository
            ->expects($this->never())
            ->method('findFilteredNews');

        $request = Request::create('/api/news', 'GET', [
            'date' => '2026-13-01',
        ]);

        $response = $this->controller->getNews($request, $this->newsRepository);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            ['error' => 'Invalid date. Expected format: YYYY-MM-DD.'],
            json_decode($response->getContent(), true)
        );
    }

    public function testGetNewsReturnsBadRequestForNonDateValue(): void
    {
        $this->newsRepository
            ->expects($this->never())
            ->method('findFilteredNews');

        $request = Request::create('/api/news', 'GET', [
            'date' => 'abc',
        ]);

        $response = $this->controller->getNews($request, $this->newsRepository);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            ['error' => 'Invalid date. Expected format: YYYY-MM-DD.'],
            json_decode($response->getContent(), true)
        );
    }

    public function testGetNewsReturnsInternalServerErrorOnRepositoryException(): void
    {
        $this->newsRepository
            ->expects($this->once())
            ->method('findFilteredNews')
            ->willThrowException(new \Exception('Database connection failed'));

        $request = Request::create('/api/news', 'GET', []);

        $response = $this->controller->getNews($request, $this->newsRepository);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(
            ['error' => 'Internal server error'],
            json_decode($response->getContent(), true)
        );
    }
}
