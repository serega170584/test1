<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MinimalRemain;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<MinimalRemain>
 */
class MinimalRemainRepository extends DoctrineRepository implements MinimalRemainRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MinimalRemain::class);
    }

    /**
     * @param array<string> $articles
     *
     * @return array<MinimalRemain>
     */
    public function findAllByArticles(array $articles): array
    {
        return $this->createQueryBuilder('mr')
                    ->where('mr.article IN (:articles)')
                    ->setParameter('articles', $articles)
                    ->getQuery()
                    ->getResult();
    }

    public function deleteByArticles(array $articles): void
    {
        $this->createQueryBuilder('mr')
             ->delete()
             ->where('mr.article IN (:articles)')->setParameter('articles', $articles)
             ->getQuery()
             ->getResult();
    }
}
