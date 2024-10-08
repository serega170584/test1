<?php

declare(strict_types=1);

namespace App\Metric\MetricsCollector;

use App\Service\FullImportPROVIDERGoodsCountersService;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Prometheus\Exception\MetricsRegistrationException;

class FullImportPROVIDERGoodsMetricsCollector implements MetricsCollectorInterface
{
    use MetricsCollectorInitTrait;

    public function __construct(
        private readonly FullImportPROVIDERGoodsCountersService $countersService,
    ) {
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function collect(): void
    {
        $metric = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'full_import_PROVIDER_goods_started_stores',
            'Started stores count for full import PROVIDER goods',
        );
        $metric->set($this->countersService->getStartedStoresCount());

        $metric = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'full_import_PROVIDER_goods_received_divisions',
            'Received divisions count for full import PROVIDER goods',
        );
        $metric->set($this->countersService->getReceivedDivisionsCount());

        $metric = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'full_import_PROVIDER_goods_processed_divisions',
            'Processed divisions count for full import PROVIDER goods',
        );
        $metric->set($this->countersService->getProcessedDivisionsCount());

        $metric = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'full_import_PROVIDER_goods_invalid_divisions',
            'Invalid divisions count for full import PROVIDER goods',
        );
        $metric->set($this->countersService->getInvalidDivisionsCount());
    }
}
