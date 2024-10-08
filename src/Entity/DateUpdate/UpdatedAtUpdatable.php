<?php

declare(strict_types=1);

namespace App\Entity\DateUpdate;

use App\EventSubscriber\DatabaseSubscriber;
use DateTimeImmutable;

/**
 * Метод 'setUpdatedAt' сущности, которая реализует данный интерфейс, будет вызываться, когда модель обновляется в БД.
 */
interface UpdatedAtUpdatable
{
    /**
     * @see DatabaseSubscriber::preUpdate()
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt): self;
}
