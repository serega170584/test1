<?php

namespace App\Tests\Controller\PROVIDER;

use App\Entity\Order;
use App\Manager\FeatureManager;
use App\Message\OrderReserveRemoving;
use App\Repository\OrderRepository;
use App\Request\Dto\PROVIDER\OrderStatusInterface as PROVIDEROrderStatus;
use App\Request\Dto\PROVIDER\v1\OrderReceiptDto;
use App\Tests\Support\Dto\OrderDto;
use App\Tests\Support\Dto\RequestDto;
use App\Tests\Support\Generator\OrderDtoGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Flagception\Manager\FeatureManagerInterface;
use test1\Message\V2\ImportOrderReceipt;
use test1\Message\V2\ImportOrderReceiptItem;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Serializer\SerializerInterface;

class OrdersControllerCompleteOrderTest extends WebTestCase
{
    /**
     * Тест запроса из УАС на завершение заказа.
     *
     * @dataProvider dataProvider
     */
    public function testCompleteOrder(
        OrderDto $orderDto,
        bool $isEnabledDistributorReserves
    ): void {
        $client = static::createClient();

        $this->initOrderFixture($orderDto);
        $requestDto = $this->makeRequestDto($orderDto);

        $this->initMockFeatureManager($isEnabledDistributorReserves);

        $this->executeHttpRequest($client, $requestDto);
        self::assertResponseIsSuccessful();

        $expectedImportOrderReceiptMessage = $this->makeExpectedImportOrderReceiptMessage($orderDto);
        $producedImportOrderReceiptMessage = $this->getProducedImportOrderReceiptMessage();
        self::assertEquals($expectedImportOrderReceiptMessage, $producedImportOrderReceiptMessage);

        $producedReserveRemovingMessage = $this->getProducedReserveRemovingMessage();
        $expectedReserveRemovingMessage = $this->makeExpectedOrderReserveRemovingMessage($orderDto);
        self::assertEquals($expectedReserveRemovingMessage, $producedReserveRemovingMessage);

        self::assertNull($this->findOrderEntity($orderDto->getOrderId()));
    }

    private function makeRequestDto(OrderDto $orderDto): RequestDto
    {
        $rows = [];

        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $rows[] = [
                'Quantity' => $cartItemDto->getQuantity(),
                'Sum' => $cartItemDto->getQuantity() * $cartItemDto->getPrice(),
                'Product' => [
                    'Code' => $cartItemDto->getProductArticle(),
                    'Name' => $cartItemDto->getProductName(),
                ],
            ];
        }

        return (new RequestDto())
            ->setMethod(Request::METHOD_PATCH)
            ->setUrl('/api/PROVIDER/v2/orders/complete-order')
            ->setBody(
                $this->serializeToJson(
                    [
                        'Id' => $orderDto->getRequestId(),
                        'Order' => $orderDto->getOrderId(),
                        'Type' => OrderReceiptDto::TYPE_SALE,
                        'Rows' => $rows,
                    ]
                )
            );
    }

    private function executeHttpRequest(KernelBrowser $client, RequestDto $requestDto): void
    {
        $client->request(
            $requestDto->getMethod(),
            $requestDto->getUrl(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $requestDto->getBody()
        );
    }

    private function getProducedImportOrderReceiptMessage(): ?object
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.v2_import_orders');
        $envelops = $transport->getSent();

        return $envelops ? $envelops[0]->getMessage() : null;
    }

    private function getProducedReserveRemovingMessage(): ?object
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.remove_order_reserves');
        $envelops = $transport->getSent();

        return $envelops ? $envelops[0]->getMessage() : null;
    }

    private function makeExpectedImportOrderReceiptMessage(OrderDto $orderDto): ImportOrderReceipt
    {
        $items = [];

        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $items[] = new ImportOrderReceiptItem(
                $cartItemDto->getQuantity(),
                $cartItemDto->getQuantity() * $cartItemDto->getPrice(),
                $cartItemDto->getProductArticle(),
                $cartItemDto->getProductName(),
            );
        }

        return (new ImportOrderReceipt(
            $orderDto->getOrderId(),
            OrderReceiptDto::TYPE_SALE,
            $items
        ))
            ->setRequestId($orderDto->getRequestId());
    }

    private function makeExpectedOrderReserveRemovingMessage(OrderDto $orderDto): ?OrderReserveRemoving
    {
        if (!$orderDto->getDateOrderExecution()) {
            return null;
        }

        return new OrderReserveRemoving($orderDto->getOrderId());
    }

    private function initOrderFixture(OrderDto $orderDto): void
    {
        $container = self::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        /** @var OrderRepository $orderRepo */
        $orderRepo = $entityManager->getRepository(Order::class);

        $orderEntity = (new Order())
            ->setId($orderDto->getOrderId())
            ->setIsDeliveryToCustomer((bool) $orderDto->getAddressDelivery())
            ->setIsDistributor((bool) $orderDto->getDateOrderExecution());
        $orderRepo->save($orderEntity);
    }

    private function initMockFeatureManager(bool $isEnabledDistributorReserves): void
    {
        $featureManager = $this->createMock(FeatureManagerInterface::class);
        $featureManager
            ->method('isActive')
            ->willReturnMap(
                [
                    [FeatureManager::IS_ENABLED_MONOLITH_KAFKA, null, false],
                    [FeatureManager::IS_ENABLED_DELIVERY_TO_CUSTOMER, null, false],
                    [FeatureManager::IS_ENABLED_DISTRIBUTORS_RESERVES, null, $isEnabledDistributorReserves],
                ]
            );

        self::getContainer()->set('flagception.manager.feature_manager', $featureManager);
    }

    private function serializeToJson(array $data): string
    {
        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get('serializer');

        return $serializer->serialize($data, 'json');
    }

    private function findOrderEntity(int $orderId): ?Order
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        return $entityManager->getRepository(Order::class)->find($orderId);
    }

    /**
     * @throws Exception
     */
    public function dataProvider(): iterable
    {
        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_COMPLETED);

        yield 'disabled_distributor_reserves' => [$orderDto, false];
        yield 'enabled_distributor_reserves' => [$orderDto, true];

        $orderDto = (new OrderDtoGenerator())
            ->withAddressDelivery()
            ->withCart()
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_COMPLETED);

        yield 'with_address_delivery--disabled_distributor_reserves' => [$orderDto, false];
        yield 'with_address_delivery--enabled_distributor_reserves' => [$orderDto, true];

        $orderDto = (new OrderDtoGenerator())
            ->withDateOrderExecution()
            ->withCart()
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_COMPLETED);

        yield 'with_date_order_execution--enabled_distributor_reserves' => [$orderDto, true];
    }
}
