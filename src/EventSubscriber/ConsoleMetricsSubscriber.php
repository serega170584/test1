<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Metric\MetricsCollector\ConsoleMetricsCollector;
use Prometheus\Exception\MetricsRegistrationException;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleMetricsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ConsoleMetricsCollector $metricsCollector
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onCommand',
            ConsoleEvents::TERMINATE => 'onTerminate',
        ];
    }

    public function onCommand(): void
    {
        $this->metricsCollector->start();
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command) {
            return;
        }
        $this->metricsCollector->stop($command, $event->getExitCode());
    }
}
