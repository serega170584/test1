<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\FullImportPROVIDERGoods\DivisionInvalidedEvent;
use App\Event\FullImportPROVIDERGoods\DivisionItemInvalidedEvent;
use App\Event\FullImportPROVIDERGoods\DivisionItemProcessedEvent;
use App\Event\FullImportPROVIDERGoods\DivisionProcessedEvent;
use App\Event\FullImportPROVIDERGoods\DivisionsReceivedEvent;
use App\Event\FullImportPROVIDERGoods\StartedEvent;
use App\Metric\MetricsCollector\FullImportPricesAndRemainsFinishedMetricsCollector;
use App\Service\FullImportPROVIDERGoodsCountersService;
use App\Service\MonolithFullImportRemainService;
use Prometheus\Exception\MetricsRegistrationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class FullImportPROVIDERGoodsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MonolithFullImportRemainService $monolithFullImportRemainService,
        private readonly FullImportPROVIDERGoodsCountersService $fullImportPROVIDERGoodsCounterService,
        private readonly FullImportPricesAndRemainsFinishedMetricsCollector $fullImportPricesAndRemainsFinishedMetricsCollector,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StartedEvent::class => 'onStarted',
            DivisionsReceivedEvent::class => 'onDivisionsReceived',
            DivisionProcessedEvent::class => 'onDivisionProcessed',
            DivisionItemProcessedEvent::class => 'onDivisionItemProcessed',
            DivisionInvalidedEvent::class => 'onDivisionInvalided',
            DivisionItemInvalidedEvent::class => 'onDivisionItemInvalided',
        ];
    }

    /**
     * @throws MetricsRegistrationException
     * @throws TransportExceptionInterface
     */
    public function onStarted(StartedEvent $event): void
    {
        $countStoreIds = count($event->storeIds);
        $this->fullImportPricesAndRemainsFinishedMetricsCollector->set(0);
        $this->fullImportPROVIDERGoodsCounterService->reset($countStoreIds);
        $this->monolithFullImportRemainService->resetFullImportRemainCount();
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function onDivisionsReceived(DivisionsReceivedEvent $event): void
    {
        $this->fullImportPROVIDERGoodsCounterService->incrementReceivedDivisionsCount(count($event->divisions));
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function onDivisionProcessed(): void
    {
        $this->fullImportPROVIDERGoodsCounterService->incrementProcessedDivisionsCount();

        $receivedDivisionsCount = $this->fullImportPROVIDERGoodsCounterService->getReceivedDivisionsCount();
        $processedDivisionsCount = $this->fullImportPROVIDERGoodsCounterService->getProcessedDivisionsCount();
        $invalidDivisionsCount = $this->fullImportPROVIDERGoodsCounterService->getInvalidDivisionsCount();

        if ($receivedDivisionsCount === ($processedDivisionsCount + $invalidDivisionsCount)) {
            $this->monolithFullImportRemainService->setFullImportCount(
                $invalidDivisionsCount,
                $this->fullImportPROVIDERGoodsCounterService->getProcessedDivisionItemsCount(),
            );
            $this->fullImportPricesAndRemainsFinishedMetricsCollector->set(1);
        }
    }

    public function onDivisionItemProcessed(): void
    {
        $this->fullImportPROVIDERGoodsCounterService->incrementProcessedDivisionItemsCount();
    }

    public function onDivisionInvalided(): void
    {
        $this->fullImportPROVIDERGoodsCounterService->incrementInvalidDivisionsCount();
    }

    public function onDivisionItemInvalided(): void
    {
        $this->fullImportPROVIDERGoodsCounterService->incrementInvalidDivisionItemsCount();
    }
}
