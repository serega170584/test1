<?php

declare(strict_types=1);

namespace App\Metric\MetricsCollector;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Prometheus\Exception\MetricsRegistrationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpIncomingMetricsCollector implements MetricsCollectorInterface
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
    public function stop(Request $request, Response $response): void
    {
        $currentStart = array_pop($this->startsStack);
        if (!$currentStart) {
            throw new RuntimeException('Not started timer');
        }

        $labelsNames = [
            'http_path',
            'http_method',
            'http_response_code',
        ];
        $labels = [
            $request->getPathInfo(),
            $request->getMethod(),
            $response->getStatusCode(),
        ];

        $timeDiff = round(microtime(true) - $currentStart, 3);

        $metric = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'http_requests_handling_time',
            'Http requests handling time, in sec.',
            $labelsNames
        );
        $metric->set($timeDiff, $labels);

        $metric = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'http_requests_allocated_memory',
            'Allocated memory for http request processing, in bytes',
            $labelsNames
        );
        $metric->set(memory_get_peak_usage(), $labels);

        $metric = $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'http_requests_count',
            'Count of http requests',
            $labelsNames
        );
        $metric->inc($labels);
    }
}
