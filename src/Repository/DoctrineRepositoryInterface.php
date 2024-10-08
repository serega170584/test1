<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Persistence\ObjectRepository;

/**
 * @template T
 */
interface DoctrineRepositoryInterface extends ObjectRepository
{
    /**
     * @param T $entity
     *
     * @return T
     */
    public function save($entity, bool $flush = true);
}
