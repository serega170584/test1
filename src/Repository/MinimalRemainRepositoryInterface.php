<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MinimalRemain;

interface MinimalRemainRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * @return array<MinimalRemain>
     */
    public function findAllByArticles(array $articles): array;

    public function deleteByArticles(array $articles): void;
}
