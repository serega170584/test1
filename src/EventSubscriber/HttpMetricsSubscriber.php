<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Metric\MetricsCollector\HttpIncomingMetricsCollector;
use Prometheus\Exception\MetricsRegistrationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class HttpMetricsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly HttpIncomingMetricsCollector $metricsCollector
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onRequest', 100000],
            TerminateEvent::class => ['onTerminate', -100000],
        ];
    }

    public function onRequest(): void
    {
        $this->metricsCollector->start();
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function onTerminate(TerminateEvent $event): void
    {
        $this->metricsCollector->stop($event->getRequest(), $event->getResponse());
    }
}
