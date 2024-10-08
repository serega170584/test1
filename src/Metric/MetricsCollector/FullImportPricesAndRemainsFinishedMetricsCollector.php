<?php

declare(strict_types=1);

namespace App\Metric\MetricsCollector;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Prometheus\Exception\MetricsRegistrationException;

class FullImportPricesAndRemainsFinishedMetricsCollector implements MetricsCollectorInterface
{
    use MetricsCollectorInitTrait;

    /**
     * @throws MetricsRegistrationException
     */
    public function set(int $value): void
    {
        $metric = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'full_import_prices_and_remains_finished',
            'Full import prices and remains finished',
        );

        $metric->set($value);
    }
}
