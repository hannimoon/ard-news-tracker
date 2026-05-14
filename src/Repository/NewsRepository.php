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
}
