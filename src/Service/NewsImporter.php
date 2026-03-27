<?php
namespace App\Service;

use App\Entity\News;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
// use MongoDB\Client as MongoClient; // Erstmal weggelassen
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NewsImporter
{
    private const ARD_API_URL = 'https://tagesschau.de/api2u/news/?regions=8&ressort=inland';

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private NewsRepository $newsRepository,
        // private string $mongoUri // MongoDB vorerst deaktiviert
    ) {}

    public function import(): int
    {
        $importedCount = 0;

        try {
            // 1. API Request an die ARD
            $response = $this->httpClient->request('GET', self::ARD_API_URL);
            $statusCode = $response->getStatusCode();
            $content = $response->toArray();

            // 2. Logging (Vorbereitet für MongoDB)
            // $this->logToMongo($statusCode, $content);

            if ($statusCode !== 200) {
                throw new \Exception("API Fehler: Status $statusCode");
            }

            // 3. Verarbeitung der News-Einträge
            foreach ($content['news'] as $item) {
                // Eindeutige ID aus der API (externalId oder shareurl als Fallback)
                $externalId = $item['externalId'] ?? ($item['shareurl'] ?? null);

                if (!$externalId || $this->newsRepository->findOneBy(['externalId' => $externalId])) {
                    continue; // Überspringen, wenn ID fehlt oder News schon existiert
                }

                $news = new News();
                $news->setExternalId($externalId);
                $news->setTitle($item['title'] ?? 'Kein Titel');
                $news->setContent($item['firstSentence'] ?? '');
                $news->setDatePublished(new \DateTime($item['date'] ?? 'now'));
                $news->setUrl($item['shareurl'] ?? '');
                $news->setType($item['type'] ?? 'none');

                // Einfaches Sentiment-Mapping für deine React-Filter
                $textForAnalysis = ($item['title'] ?? '') . ' ' . ($item['firstSentence'] ?? '');
                $news->setSentiment($this->analyzeSentiment($textForAnalysis));

                $this->entityManager->persist($news);
                $importedCount++;
            }

            // Alles in die MySQL Datenbank schreiben
            $this->entityManager->flush();

        } catch (\Exception $e) {
            // $this->logToMongo(500, ['error' => $e->getMessage()]);
            throw $e;
        }

        return $importedCount;
    }

    /**
     * Einfache Keyword-Prüfung für Good/Bad News
     */
    private function analyzeSentiment(string $text): string
    {
        $badWords = ['Krise', 'Streik', 'Tot', 'Unfall', 'Krieg', 'Verlust', 'Inflation', 'Pandemie'];
        $goodWords = ['Erfolg', 'Rekord', 'Hilfe', 'Rettung', 'Sieg', 'Wachstum', 'Fortschritt'];

        foreach ($badWords as $word) {
            if (stripos($text, $word) !== false) return 'bad';
        }
        foreach ($goodWords as $word) {
            if (stripos($text, $word) !== false) return 'good';
        }

        return 'neutral';
    }

    /*
    private function logToMongo(int $status, array $data): void
    {
        // Spätere Implementierung für MongoDB Logging
    }
    */
}
