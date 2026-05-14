<?php 

namespace App\Controller;

use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use App\Entity\News;

#[Route('/api/news', name: 'api_news_')]
#[OA\Tag(name: 'News', description: 'Operations for querying news articles with filtering and pagination.')]
class NewsApiController extends AbstractController
{
    private const DEFAULT_TYPE = 'story';
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_LIMIT = 10;
    private const MAX_LIMIT = 1000;
    private const POSITIVE_NUMBER_PATTERN = '/^[1-9]\d*$/';
    private const ALLOWED_TYPES = ['webview', 'story'];
    private const ALLOWED_SENTIMENTS = ['good', 'bad', 'neutral'];

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        operationId: 'getNewsList',
        summary: 'Retrieves news list',
        description: 'Retrieves published news items and supports filtering by region, date, sentiment, type, and pagination.',
        security: [
            ['BearerAuth' => []], 
            ['ApiKeyAuth' => []]
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Returns filtered news list with pagination',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data', 
                            type: 'array', 
                            items: new OA\Items(ref: new Model(type: News::class))
                        ),
                        new OA\Property(
                            property: 'meta', 
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer'),
                                new OA\Property(property: 'page', type: 'integer'),
                                new OA\Property(property: 'limit', type: 'integer'),
                                new OA\Property(property: 'pages', type: 'integer')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request for invalid query parameters',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized - Authentication required'),
        ]
    )]
    #[OA\Parameter(name: 'region', in: 'query', description: 'Filter for region', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'date', in: 'query', description: 'Filter for date (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'sentiment', in: 'query', description: 'Filter for sentiment (supported values: good, bad, neutral)', schema: new OA\Schema(type: 'string', enum: ['good', 'bad', 'neutral']))]
    #[OA\Parameter(name: 'type', in: 'query', description: 'Filter for type (supported values: webview, story)', schema: new OA\Schema(type: 
        'string', default: self::DEFAULT_TYPE, enum: ['webview', 'story']))]
    #[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: self::DEFAULT_PAGE, minimum: 1))]
    #[OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: self::DEFAULT_LIMIT, minimum: 1, maximum: 1000))]
  
    public function getNews(Request $request, NewsRepository $newsRepository): JsonResponse
    {
        try {
            $region = self::normalizeQueryValue($request->query->get('region'));
            $date = self::normalizeDate($request->query->get('date'));
            if ($date === false) {
                return self::errorResponse('Invalid date. Expected format: YYYY-MM-DD.');
            }

            $sentiment = self::validateEnum(self::normalizeQueryValue($request->query->get('sentiment')), self::ALLOWED_SENTIMENTS);
            if ($sentiment === false) {
                return self::errorResponse('Invalid sentiment. Allowed values are good, bad, or neutral.');
            }

            $type = self::validateEnum(self::normalizeQueryValue($request->query->get('type')), self::ALLOWED_TYPES, self::DEFAULT_TYPE);
            if ($type === false) {
                return self::errorResponse('Invalid type. Allowed values are webview or story.');
            }
            
            $page = self::normalizePositiveInt($request->query->get('page'), self::DEFAULT_PAGE);
            $limit = self::normalizePositiveInt($request->query->get('limit'), self::DEFAULT_LIMIT, self::MAX_LIMIT);

            $newsData = $newsRepository->findFilteredNews(
                $region,
                $date,
                $sentiment,
                $type,
                $page,
                $limit
            );

            return new JsonResponse($newsData);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    private static function normalizeQueryValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

   
    private static function normalizePositiveInt(?string $value, int $default, ?int $max = null): int
    {
        if ($value === null || trim($value) === '') {
            return $default;
        }

        $value = trim($value);
        if (!preg_match(self::POSITIVE_NUMBER_PATTERN, $value)) {
            return $default;
        }

        $intValue = (int) $value;
        if ($max !== null && $intValue > $max) {
            return $max;
        }

        return $intValue;
    }

    private static function validateEnum(?string $value, array $allowed, ?string $default = null): string|false|null
    {
        if ($value === null) {
            return $default;
        }

        return in_array($value, $allowed, true) ? $value : false;
    }

    private static function errorResponse(string $message): JsonResponse
    {
        return new JsonResponse(['error' => $message], JsonResponse::HTTP_BAD_REQUEST);
    }

    private static function normalizeDate(?string $date): string|false|null
    {
        if ($date === null) {
            return null;
        }

        $date = trim($date);
        if ($date === '') {
            return null;
        }

        // Validate date format (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        $dateTime = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($dateTime === false) {
            return false;
        }

        return $dateTime->format('Y-m-d') === $date ? $date : false;
    }
}