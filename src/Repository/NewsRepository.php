<?php

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<News>
 *
 * @author Mandy Vorköper <mandy-vor@gmx.de>
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    public function findFilteredNews(?string $region, ?string $date, ?string $sentiment, ?string $type, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('n')
            ->orderBy('n.datePublished', 'DESC');

        if ($region) {
            $qb->andWhere('n.region = :region')
               ->setParameter('region', $region);
        }

        if ($date) {
            $qb->andWhere('n.datePublished = :date')
               ->setParameter('date', new \DateTime($date));
        }

        if ($sentiment !== null) {
            $qb->andWhere('n.sentiment = :sentiment')
               ->setParameter('sentiment', $sentiment);
        }
        
        if ($type) {
            $qb->andWhere('n.type = :type')
               ->setParameter('type', $type);
        }

        // Pagination Logik
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb);
        $totalItems = count($paginator);

        return [
            'data' => $qb->getQuery()->getArrayResult(),
            'meta' => [
                'total' => $totalItems,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($totalItems / $limit)
            ]
        ];
    }


    /**
     *
     * Returns the number of imported news items per day for the last six months.
     *
     * @return array An array of arrays, each containing 'dateStr' and 'count' keys.
     */
    public function countImportedNewsPerDay(): array
    {
        return $this->createQueryBuilder('n')
            ->select("SUBSTRING(n.importedAt, 1, 10) as dateStr, COUNT(n.id) as count")
            ->where('n.importedAt >= :minusSixMonths')
            ->setParameter('minusSixMonths', (new \DateTime())->modify('-6 months')->format('Y-m-d H:i:s'))
            ->groupBy('dateStr')
            ->orderBy('dateStr', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
