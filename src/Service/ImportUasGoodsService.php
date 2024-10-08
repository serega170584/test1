<?php

declare(strict_types=1);

namespace App\Service;

use App\Client\PROVIDER\ApiClient;
use App\Client\PROVIDER\DivisionsItemsDto;
use App\Event\FullImportPROVIDERGoods\DivisionInvalidedEvent;
use App\Event\FullImportPROVIDERGoods\DivisionItemInvalidedEvent;
use App\Event\FullImportPROVIDERGoods\DivisionItemProcessedEvent;
use App\Event\FullImportPROVIDERGoods\DivisionProcessedEvent;
use App\Event\FullImportPROVIDERGoods\DivisionsReceivedEvent;
use App\Event\FullImportPROVIDERGoods\StartedEvent;
use App\Message\PROVIDERGoodsProcess;
use App\Message\PROVIDERGoodsRequest;
use App\Reader\StoreReaderInterface;
use App\Request\Dto\PROVIDER\v1\DivisionDto;
use App\Request\Dto\PROVIDER\v1\DivisionItemDto;
use test1\Message\V1\ImportRemain;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImportPROVIDERGoodsService
{
    public const CHUNK_SIZE_IMPORTING_STORE_IDS = 5;

    public function __construct(
        private readonly StoreReaderInterface $storeReader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ApiClient $PROVIDERApiClient,
        private readonly LoggerInterface $logger,
        private readonly SubtractRemainManagerInterface $subtractStockManager,
        private readonly ValidatorInterface $validator,
        private readonly MessageBusInterface $bus,
        private readonly SnPAdapterService $snpAdapterService
    ) {
    }

    /**
     * Ставит в очередь задания на получение остатков из УАС.
     *
     * @param array|null $storeIds Если передан пустой массив или null, то будет запущен полный импорт, т.е. по всем аптекам
     */
    public function dispatchPROVIDERGoodsRequests(?array $storeIds = null): void
    {
        $isFullImport = empty($storeIds);

        if (!$storeIds) {
            $storeIds = $this->storeReader->readAll();
        }
        $storeIds = array_unique($storeIds);

        if ($isFullImport) {
            $this->eventDispatcher->dispatch(new StartedEvent($storeIds));
        }

        $this->logger->info('[Import PROVIDER goods] Dispatch PROVIDER goods requests', [
            'store_ids' => $storeIds,
            'is_full_import' => $isFullImport,
        ]);

        foreach (array_chunk($storeIds, self::CHUNK_SIZE_IMPORTING_STORE_IDS) as $chunkStoreIds) {
            $this->bus->dispatch(new PROVIDERGoodsRequest($chunkStoreIds, $isFullImport));
        }
    }

    /**
     * Получение остатков из УАС для указанных аптек и отправка задания в очередь на их обработку.
     */
    public function requestPROVIDERGoods(array $storeIds, bool $isFullImport = false): void
    {
        $divisionDtos = $this->PROVIDERApiClient->requestDivisions(new DivisionsItemsDto($storeIds));

        if ($isFullImport) {
            $divisions = [];
            foreach ($divisionDtos as $divisionDto) {
                $divisions[] = $divisionDto->getDivision();
            }
            $this->eventDispatcher->dispatch(new DivisionsReceivedEvent($divisions));
            $notReceivedStoreIds = array_diff($storeIds, $divisions);
            if ($notReceivedStoreIds) {
                $this->logger->warning('[Import PROVIDER goods] Not received stores', [
                    'store_ids' => $notReceivedStoreIds,
                ]);
            }
        }

        // Сделано так для ускорения импорта, т.к. обработка резервов и минимальных остатков занимает время
        $this->bus->dispatch(new PROVIDERGoodsProcess($divisionDtos, $isFullImport));
    }

    /**
     * Обработка остатков, полученных из УАС.
     */
    public function processPROVIDERGoods(array $divisionDtos, bool $isFullImport = false): void
    {
        $subtractStocks = $this->subtractStockManager->calculateStockSubtraction($divisionDtos);

        /** @var DivisionDto $divisionDto */
        foreach ($divisionDtos as $divisionDto) {
            if (!$this->isValidDivisionDto($divisionDto)) {
                if ($isFullImport) {
                    $this->eventDispatcher->dispatch(new DivisionInvalidedEvent($divisionDto->getDivision()));
                }
                continue;
            }

            $snpDivisionDto = (new DivisionDto())->setDivision($divisionDto->getDivision());

            foreach ($divisionDto->getItems() as $divisionItemDto) {
                if (!$this->isValidDivisionItem($divisionDto, $divisionItemDto, $isFullImport)) {
                    if ($isFullImport) {
                        $this->eventDispatcher->dispatch(
                            new DivisionItemInvalidedEvent($divisionDto->getDivision(), $divisionItemDto->getCode())
                        );
                    }
                    continue;
                }
                $subtractStockKey = $this->subtractStockManager->getSubtractionKey($divisionDto->getDivision(), $divisionItemDto->getCode());
                $subtractStock = $subtractStocks[$subtractStockKey] ?? 0;
                $quantity = max((int) $divisionItemDto->getQuantity() - $subtractStock, 0);

                $message = new ImportRemain(
                    $divisionDto->getDivision(),
                    $divisionItemDto->getCode(),
                    $quantity,
                    (string) $divisionItemDto->getPrice(),
                    null,
                    null,
                    null,
                    $isFullImport
                );

                $snpDivisionDto->addItem(
                    (clone $divisionItemDto)->setQuantity($quantity)
                );

                $this->bus->dispatch($message, [new AmqpStamp('import-remain')]);

                if ($isFullImport) {
                    $this->eventDispatcher->dispatch(
                        new DivisionItemProcessedEvent($divisionDto->getDivision(), $divisionItemDto->getCode())
                    );
                }
            }

            $this->snpAdapterService->processPROVIDERPrices($snpDivisionDto);
            $this->snpAdapterService->processPROVIDERStocks($snpDivisionDto);

            if ($isFullImport) {
                $this->eventDispatcher->dispatch(
                    new DivisionProcessedEvent($divisionDto->getDivision())
                );
            }
        }
    }

    private function isValidDivisionDto(DivisionDto $divisionDto): bool
    {
        $validationErrors = $this->validator->validate($divisionDto, null, ['full_import']);
        if ($validationErrors->count()) {
            $errors = $this->getErrorsArray($validationErrors);
            $this->logger->warning('[Import PROVIDER goods] Received invalid division', [
                'division' => $divisionDto->getDivision(),
                'errors' => $errors,
            ]);

            return false;
        }

        return true;
    }

    private function isValidDivisionItem(DivisionDto $divisionDto, DivisionItemDto $divisionItemDto, bool $isFullImport): bool
    {
        $groups = ['Default', 'remains'];
        if ($isFullImport) {
            $groups[] = 'prices';
        }
        $validationErrors = $this->validator->validate($divisionItemDto, null, $groups);
        if ($validationErrors->count()) {
            $errors = $this->getErrorsArray($validationErrors);
            $this->logger->warning('[Import PROVIDER goods] Received invalid division item', [
                'division' => $divisionDto->getDivision(),
                'code' => $divisionItemDto->getCode(),
                'quantity' => $divisionItemDto->getQuantity(),
                'price' => $divisionItemDto->getPrice(),
                'is_full_import' => $isFullImport,
                'errors' => $errors,
            ]);

            return false;
        }

        return true;
    }

    private function getErrorsArray(ConstraintViolationListInterface $validationErrors): array
    {
        $errors = [];
        foreach ($validationErrors as $validationError) {
            $errors[$validationError->getPropertyPath()][] = $validationError->getMessage();
        }

        return $errors;
    }

    /**
     * Обработка цен, полученных из УАС.
     */
    public function processPROVIDERPrices(array $divisionDtos): void
    {
        /** @var DivisionDto $division */
        foreach ($divisionDtos as $division) {
            $this->snpAdapterService->processPROVIDERPrices($division);

            foreach ($division->getItems() as $item) {
                $message = new ImportRemain(
                    $division->getDivision(),
                    $item->getCode(),
                    null,
                    (string) $item->getPrice()
                );

                $this->bus->dispatch($message, [
                    new AmqpStamp('import-remain'), // TODO выпилить после переезда на кафку
                ]);
            }
        }
    }
}
