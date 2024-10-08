<?php

declare(strict_types=1);

namespace App\Entity\DateUpdate;

use App\EventSubscriber\DatabaseSubscriber;
use DateTimeImmutable;

/**
 * Метод 'setCreatedAt' сущности, которая реализует данный интерфейс, будет вызываться, когда модель сохраняется
 * впервые в БД.
 *
 * @see DatabaseSubscriber::prePersist()
 */
interface CreatedAtUpdatable
{
    public function setCreatedAt(DateTimeImmutable $createdAt): self;
}
