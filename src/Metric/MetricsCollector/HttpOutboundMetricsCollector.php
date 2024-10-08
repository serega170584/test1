<?php

declare(strict_types=1);

namespace App\Metric\MetricsCollector;

use App\Client\RequestInterface;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Prometheus\Exception\MetricsRegistrationException;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpOutboundMetricsCollector implements MetricsCollectorInterface
{
    use MetricsCollectorInitTrait;

    private array $startsStack = [];

    public function start(): void
    {
        $this->startsStack[] = microtime(true);
    }

    /**
     * @throws MetricsRegistrationException
     * @throws TransportExceptionInterface
     */
    public function stop(RequestInterface $request, ?ResponseInterface $response): void
    {
        $currentStart = array_pop($this->startsStack);

        if (!$currentStart) {
            throw new RuntimeException('Not started timer');
        }

        $labelsNames = [
            'http_host',
            'http_path',
            'http_method',
            'http_response_code',
        ];
        $labels = [
            $this->getHostWithScheme($request),
            $this->getPath($request),
            $request->getMethod(),
            $response?->getStatusCode() ?? 0,
        ];

        $timeDiff = round(microtime(true) - $currentStart, 3);

        $metric = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'http_outbound_requests_handling_time',
            'Http outbound requests handling time, in sec.',
            $labelsNames
        );
        $metric->set($timeDiff, $labels);

        $metric = $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'http_outbound_requests_count',
            'Count of http outbound requests',
            $labelsNames
        );
        $metric->inc($labels);
    }

    private function getHostWithScheme(RequestInterface $request): string
    {
        $parsedUrl = parse_url($request->getUri());

        return "{$parsedUrl['scheme']}://{$parsedUrl['host']}";
    }

    private function getPath(RequestInterface $request): string
    {
        // TODO потом найти другое решение
        if (preg_match('%^/e-shop/v2/orders/\d+%', $request->getPath())) {
            return '/e-shop/v2/orders/{orderId}';
        }

        return $request->getPath();
    }
}
