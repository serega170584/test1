<?php

declare(strict_types=1);

namespace App\Messenger\Middleware;

use App\Metric\MetricsCollector\MessageMetricsCollector;
use Prometheus\Exception\MetricsRegistrationException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class MetricsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MessageMetricsCollector $metricsCollector
    ) {
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->metricsCollector->start();

        $result = $stack->next()->handle($envelope, $stack);

        $this->metricsCollector->stop($envelope);

        return $result;
    }
}
