<?php

declare(strict_types=1);

namespace App\Service;

use Predis\ClientInterface;
use RuntimeException;

class FullImportPROVIDERGoodsCountersService
{
    private const STARTED_STORES_COUNTER_CODE = 'full_import_PROVIDER_goods_started_stores';

    private const RECEIVED_DIVISIONS_COUNTER_CODE = 'full_import_PROVIDER_goods_received_divisions';

    private const PROCESSED_DIVISIONS_COUNTER_CODE = 'full_import_PROVIDER_goods_processed_divisions';

    private const PROCESSED_DIVISION_ITEMS_COUNTER_CODE = 'full_import_PROVIDER_goods_processed_division_items';

    private const INVALID_DIVISIONS_COUNTER_CODE = 'full_import_PROVIDER_goods_invalid_divisions';

    private const INVALID_DIVISION_ITEMS_COUNTER_CODE = 'full_import_PROVIDER_goods_invalid_division_items';

    public function __construct(
        private readonly ClientInterface $predis
    ) {
    }

    public function reset(int $startedStoresCount): void
    {
        $this->predis->set(self::STARTED_STORES_COUNTER_CODE, $startedStoresCount);

        $this->predis->set(self::RECEIVED_DIVISIONS_COUNTER_CODE, 0);

        $this->predis->set(self::PROCESSED_DIVISIONS_COUNTER_CODE, 0);

        $this->predis->set(self::PROCESSED_DIVISION_ITEMS_COUNTER_CODE, 0);

        $this->predis->set(self::INVALID_DIVISIONS_COUNTER_CODE, 0);

        $this->predis->set(self::INVALID_DIVISION_ITEMS_COUNTER_CODE, 0);
    }

    public function incrementReceivedDivisionsCount(int $count): void
    {
        $this->predis->incrby(self::RECEIVED_DIVISIONS_COUNTER_CODE, $count);
    }

    public function incrementProcessedDivisionsCount(): void
    {
        $this->predis->incr(self::PROCESSED_DIVISIONS_COUNTER_CODE);
    }

    public function incrementProcessedDivisionItemsCount(): void
    {
        $this->predis->incr(self::PROCESSED_DIVISION_ITEMS_COUNTER_CODE);
    }

    public function incrementInvalidDivisionsCount(): void
    {
        $this->predis->incr(self::INVALID_DIVISIONS_COUNTER_CODE);
    }

    public function incrementInvalidDivisionItemsCount(): void
    {
        $this->predis->incr(self::INVALID_DIVISION_ITEMS_COUNTER_CODE);
    }

    public function getStartedStoresCount(): int
    {
        return $this->getCounterValue(self::STARTED_STORES_COUNTER_CODE);
    }

    public function getReceivedDivisionsCount(): int
    {
        return $this->getCounterValue(self::RECEIVED_DIVISIONS_COUNTER_CODE);
    }

    public function getProcessedDivisionsCount(): int
    {
        return $this->getCounterValue(self::PROCESSED_DIVISIONS_COUNTER_CODE);
    }

    public function getInvalidDivisionsCount(): int
    {
        return $this->getCounterValue(self::INVALID_DIVISIONS_COUNTER_CODE);
    }

    public function getProcessedDivisionItemsCount(): int
    {
        return $this->getCounterValue(self::PROCESSED_DIVISION_ITEMS_COUNTER_CODE);
    }

    private function getCounterValue(string $code): int
    {
        $count = $this->predis->get($code);
        if (is_null($count)) {
            return 0;
        }

        return (int) $count;
    }
}
