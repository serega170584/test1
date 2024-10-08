<?php

declare(strict_types=1);

namespace App\Messenger\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

class DoctrineClearMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $result = $stack->next()->handle($envelope, $stack);

        // Очистка EntityManager после получения и обработки сообщения
        if ($envelope->last(ReceivedStamp::class)) {
            $this->entityManager->clear();
        }

        return $result;
    }
}
