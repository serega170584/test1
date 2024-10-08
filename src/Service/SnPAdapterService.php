<?php
declare(strict_types=1);

namespace App\Service;

use App\Client\SnPAdapter\Client;
use App\Common\Log\LoggerContextEnum;
use App\Manager\FeatureManager;
use App\Request\Dto\PROVIDER\v1\DivisionDto;
use EmptyIterator;
use Platform\test_corp_adapter\Price;
use Platform\test_corp_adapter\PricesRequest;
use Platform\test_corp_adapter\Source;
use Platform\test_corp_adapter\Stock;
use Platform\test_corp_adapter\StocksRequest;
use Psr\Log\LoggerInterface;

final class SnPAdapterService
{
    public const MAX_BATCH_ITEMS = 1000;

    public const STORE_ID_TEMPLATE = '%s_PROVIDER';

    public const MAX_REPEAT_ATTEMPTS = 5;

    public const SLEEP_BETWEEN_REPEAT_ATTEMPTS = 1;

    public function __construct(
        private readonly Client $clientSnPAdapter,
        private readonly LoggerInterface $logger,
        private readonly FeatureManager $featureManager
    ) {
    }

    public function processPROVIDERStocks(DivisionDto $divisionDto): void
    {
        if (!$this->featureManager->isEnabledSendingToSNP()) {
            return;
        }

        foreach ($this->convertDivisionDtoToStocksRequests($divisionDto) as $request) {
            $this->tryRequest(
                function () use ($request) {
                    $this->clientSnPAdapter->pushStock($request);
                },
                "Can't push stocks to snp adapter for store_id " . $request->getStoreId()
            );
        }
    }

    /**
     * @return iterable<int, StocksRequest>
     */
    private function convertDivisionDtoToStocksRequests(DivisionDto $divisionDto): iterable
    {
        if (!$divisionDto->getItems()) {
            return new EmptyIterator();
        }

        $offset = 0;
        while ($batch = array_slice($divisionDto->getItems(), $offset, self::MAX_BATCH_ITEMS)) {
            $stocks = [];
            foreach ($batch as $batchItem) {
                $goodID = $batchItem->getCode();

                // группировка по артикулу в рамках одного батча чтобы снп не падал при попытке upsert записи
                $stocks[$goodID] = new Stock([
                    'good_id' => $goodID,
                    'quantity' => (int) $batchItem->getQuantity(),
                ]);
            }

            if ($stocks) {
                $request = new StocksRequest();
                $request->setSource(Source::SOURCE_TO);
                $request->setStoreId(sprintf(self::STORE_ID_TEMPLATE, $divisionDto->getDivision()));
                $request->setStocks(array_values($stocks));

                yield $request;
            }

            $offset += self::MAX_BATCH_ITEMS;
        }
    }

    public function processPROVIDERPrices(DivisionDto $divisionDto): void
    {
        if (!$this->featureManager->isEnabledSendingToSNP()) {
            return;
        }

        foreach ($this->convertDivisionDtoToPricesRequests($divisionDto) as $request) {
            $this->tryRequest(
                function () use ($request) {
                    $this->clientSnPAdapter->pushPrice($request);
                },
                "Can't push prices to snp adapter for store_id " . $request->getStoreId()
            );
        }
    }

    /**
     * @return iterable<int, PricesRequest>
     */
    private function convertDivisionDtoToPricesRequests(DivisionDto $divisionDto): iterable
    {
        if (!$divisionDto->getItems()) {
            return new EmptyIterator();
        }

        $offset = 0;
        while ($batch = array_slice($divisionDto->getItems(), $offset, self::MAX_BATCH_ITEMS)) {
            $prices = [];

            foreach ($batch as $batchItem) {
                if ($batchItem->getPrice()) {
                    $goodID = $batchItem->getCode();

                    // группировка по артикулу в рамках одного батча чтобы снп не падал при попытке upsert записи
                    $prices[$goodID] = new Price([
                        'good_id' => $goodID,
                        'base_price' => (int) ($batchItem->getPrice() * 100),
                    ]);
                }
            }

            if ($prices) {
                $request = new PricesRequest();
                $request->setSource(Source::SOURCE_TO);
                $request->setStoreId(sprintf(self::STORE_ID_TEMPLATE, $divisionDto->getDivision()));
                $request->setPrices(array_values($prices));

                yield $request;
            }

            $offset += self::MAX_BATCH_ITEMS;
        }
    }

    private function tryRequest(callable $sendRequest, string $errorMessage): bool
    {
        for ($currentAttempts = 0; $currentAttempts < self::MAX_REPEAT_ATTEMPTS; $currentAttempts++) {
            try {
                $sendRequest();

                return true;
            } catch (\Throwable $e) {
                $this->logger->error(
                    $errorMessage . ' / Attempt - ' . $currentAttempts,
                    [
                        LoggerContextEnum::EXCEPTION->value => $e,
                    ]
                );
            }
            sleep(self::SLEEP_BETWEEN_REPEAT_ATTEMPTS);
        }

        return false;
    }
}
