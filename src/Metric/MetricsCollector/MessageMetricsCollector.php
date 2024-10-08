<?php

declare(strict_types=1);

namespace App\Metric\MetricsCollector;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Prometheus\Exception\MetricsRegistrationException;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

class MessageMetricsCollector implements MetricsCollectorInterface
{
    use MetricsCollectorInitTrait;

    private array $startsStack = [];

    public function start(): void
    {
        $this->startsStack[] = microtime(true);
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function stop(Envelope $envelope): void
    {
        $currentStart = array_pop($this->startsStack);
        if (!$currentStart) {
            throw new RuntimeException('Not started timer');
        }

        $timeDiff = round(microtime(true) - $currentStart, 3);

        $isReceiving = (bool) $envelope->all(ReceivedStamp::class);

        $labelsNames = [
            'message_class',
            'handling_type',
        ];
        $labels = [
            get_class($envelope->getMessage()),
            $isReceiving ? 'receiving' : 'sending',
        ];

        $metric = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'message_handling_time',
            'Handling message by messenger time in sec.',
            $labelsNames
        );
        $metric->set($timeDiff, $labels);

        $metric = $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'messages_count',
            'Count messenger messages',
            $labelsNames
        );
        $metric->inc($labels);
    }
}
