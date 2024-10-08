<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Client\SnPAdapter\Client;
use App\Manager\FeatureManager;
use App\Service\SnPAdapterService;
use PHPUnit\Framework\MockObject\MockObject;
use Platform\test_corp_adapter\Price;
use Platform\test_corp_adapter\PricesReply;
use Platform\test_corp_adapter\PricesRequest;
use Platform\test_corp_adapter\StocksReply;
use Platform\test_corp_adapter\StocksRequest;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Request\Dto\PROVIDER\v1\DivisionDto;
use App\Request\Dto\PROVIDER\v1\DivisionItemDto;
use Platform\test_corp_adapter\Stock;

class SnPAdapterServiceTest extends KernelTestCase
{

    private const GOOD_ID_WITHOUT_PRICE = 'good_id_without_price';

    private SnPAdapterService $snpAdapterService;

    /**
     * @var Client|MockObject
     */
    private $clientSnPAdapter;

    protected function setUp(): void
    {
        $this->clientSnPAdapter = $this->createMock(Client::class);
        $this->logger = new NullLogger();

        $featureManager = $this->createMock(FeatureManager::class);
        $featureManager->method('isEnabledSendingToSNP')->willReturn(true);

        $this->snpAdapterService = new SnPAdapterService(
            $this->clientSnPAdapter,
            new NullLogger(),
            $featureManager
        );
    }

    public function testProcessPROVIDERStocks(): void
    {
        /** @var StocksRequest[] $callbackRequests */
        $callbackRequests = [];
        $division = $this->generateDivisionDto();
        $this->clientSnPAdapter
            ->method('pushStock')
            ->willReturnCallback(static function (StocksRequest $request) use (&$callbackRequests) {
                $callbackRequests[] = $request;

                return new StocksReply();
            });

        $this->snpAdapterService->processPROVIDERStocks($division);

        self::assertNotEmpty($callbackRequests);

        foreach ($callbackRequests as $request) {

            self::assertEquals(
                sprintf(SnPAdapterService::STORE_ID_TEMPLATE, $division->getDivision()),
                $request->getStoreId()
            );

            /** @var Stock $stock */
            foreach ($request->getStocks() as $stock) {
                $currentItem = $division->getItems()[$stock->getGoodId()];
                self::assertNotNull($currentItem);
                self::assertEquals($currentItem->getQuantity(), $stock->getQuantity());
            }
        }

        self::assertEquals(
            count($division->getItems()),
            array_sum(array_map(static fn(StocksRequest $r) => $r->getStocks()->count(), $callbackRequests))
        );
    }

    public function testProcessPROVIDERPrices(): void
    {
        /** @var PricesRequest[] $callbackRequests */
        $callbackRequests = [];
        $division = $this->generateDivisionDto();
        $this->clientSnPAdapter
            ->method('pushPrice')
            ->willReturnCallback(static function (PricesRequest $request) use (&$callbackRequests) {
                $callbackRequests[] = $request;

                return new PricesReply();
            });

        $this->snpAdapterService->processPROVIDERPrices($division);

        self::assertNotEmpty($callbackRequests);

        foreach ($callbackRequests as $request) {

            self::assertLessThanOrEqual(SnPAdapterService::MAX_BATCH_ITEMS, count($request->getPrices()));

            self::assertEquals(
                sprintf(SnPAdapterService::STORE_ID_TEMPLATE, $division->getDivision()),
                $request->getStoreId()
            );

            /** @var Price $price */
            foreach ($request->getPrices() as $price) {
                $currentItem = $division->getItems()[$price->getGoodId()];
                self::assertNotNull($currentItem);
                self::assertEquals((int)($currentItem->getPrice() * 100), $price->getBasePrice());
                self::assertNotEquals(self::GOOD_ID_WITHOUT_PRICE, $price->getGoodId());
            }
        }
        self::assertEquals(
            count($division->getItems()) - 1,
            array_sum(array_map(static fn(PricesRequest $r) => $r->getPrices()->count(), $callbackRequests))
        );
    }

    private function generateDivisionDto(): DivisionDto
    {
        $items = [];
        for ($i = 0; $i < SnPAdapterService::MAX_BATCH_ITEMS + 10; $i++) {
            $item = new DivisionItemDto();
            $item->setCode('test' . $i);
            $item->setQuantity(random_int(0, 20));
            if ($i) {
                $item->setPrice(random_int(100, 333) / 100);
            } else {
                $item->setCode(self::GOOD_ID_WITHOUT_PRICE);
            }
            $items[$item->getCode()] = $item;
        }

        return (new DivisionDto())->setDivision('123')->setItems($items);
    }
}
