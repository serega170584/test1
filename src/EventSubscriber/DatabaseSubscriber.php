<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\DateUpdate\CreatedAtUpdatable;
use App\Entity\DateUpdate\UpdatedAtUpdatable;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class DatabaseSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    /**
     * This method is called when entity is persisted to database.
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof CreatedAtUpdatable) {
            $entity->setCreatedAt(new \DateTimeImmutable());
        }
        if ($entity instanceof UpdatedAtUpdatable) {
            $entity->setUpdatedAt(new \DateTimeImmutable());
        }
    }

    /**
     * This method is called when entity is updated.
     */
    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof UpdatedAtUpdatable) {
            $entity->setUpdatedAt(new \DateTimeImmutable());
        }
    }
}
