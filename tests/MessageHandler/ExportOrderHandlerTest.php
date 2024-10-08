<?php

namespace App\Tests\MessageHandler;

use App\Entity\Order;
use App\Entity\OrderReserve;
use App\Entity\RemainReserve;
use App\Manager\FeatureManager;
use App\Request\Dto\OrderStatusInterface as ImOrderStatus;
use App\Request\Dto\OrderTypeInterface;
use App\Request\Dto\PROVIDER\OrderStatusInterface as PROVIDEROrderStatus;
use App\Tests\Support\Dto\CartDto;
use App\Tests\Support\Dto\CartItemDto;
use App\Tests\Support\Dto\OrderDto;
use App\Tests\Support\Dto\RequestDto;
use App\Tests\Support\Generator\OrderDtoGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Flagception\Manager\FeatureManagerInterface;
use test1\Message\V2\ChangeRemainQuantity;
use test1\Message\V2\ExportOrder;
use test1\Message\V2\ExportOrderItem;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;
use function PHPUnit\Framework\assertEmpty;

class ExportOrderHandlerTest extends KernelTestCase
{
    public const IM_STATUSES_FINISHED_ORDERS = [
        ImOrderStatus::STATUS_CANCELLED,
        ImOrderStatus::STATUS_FINISHED,
        ImOrderStatus::STATUS_LOST_BY_COURIER,
        ImOrderStatus::STATUS_NON_PURCHASE_ACCEPTED,
    ];

    /**
     * Тест обработки сообщения из "ИМ" при создании заказа.
     *
     * @dataProvider createdOrderProvider
     *
     * @throws Throwable
     */
    public function testCreatedOrder(OrderDto $orderDto, bool $isEnabledDeliveryToCustomer, bool $isEnabledDistributorReserves): void
    {
        $kernel = self::bootKernel();

        $this->initMockFeatureManager($isEnabledDeliveryToCustomer, $isEnabledDistributorReserves);

        $expectedRequestDto = $this->makeExpectedRequestDtoForCreatedStatus($orderDto, $isEnabledDeliveryToCustomer && $orderDto->getAddressDelivery());
        $this->initPROVIDERClientMock($orderDto, $expectedRequestDto);

        $messageStub = $this->makeMessageStubForCreatedOrder($orderDto);
        $this->sendMessageToTransport($messageStub);
        $this->executeConsume($kernel);

        // Группировка по артикулу
        $cartItemsByArticle = [];
        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $cartItemsByArticle[$cartItemDto->getProductArticle()][] = $cartItemDto;
        }

        /** @var array<CartItemDto> $cartItems */
        foreach ($cartItemsByArticle as $cartItems) {
            $cartItemDto = $cartItems[0];
            $quantity = 0;
            foreach ($cartItems as $item) {
                $quantity += $item->getQuantity();
            }
            $orderReserve = $this->findOrderReserveEntity(
                $orderDto->getOrderId(),
                $cartItemDto->getProductArticle(),
                $quantity
            );

            if (!$isEnabledDistributorReserves && $orderDto->getDateOrderExecution()) {
                self::assertEmpty($orderReserve);
            } else {
                self::assertNotEmpty($orderReserve);
            }

            $remainReserve = $this->findRemainReserveEntity(
                $orderDto->getStoreId(),
                $cartItemDto->getProductArticle(),
                $quantity
            );

            if (!$isEnabledDistributorReserves && $orderDto->getDateOrderExecution()) {
                self::assertEmpty($remainReserve);
            } else {
                self::assertNotEmpty($remainReserve);
            }
        }

        $expectedChangeRemainQuantityMessages = $this->makeExpectedChangeRemainQuantityMessages($orderDto, $isEnabledDistributorReserves);
        $producedChangeRemainQuantityMessages = $this->getProducedChangeRemainQuantityMessages();
        self::assertEmpty(array_udiff($expectedChangeRemainQuantityMessages, $producedChangeRemainQuantityMessages, [$this, 'compareChangeRemainQuantityArrays']));

        $orderEntity = $this->findOrderEntity((int) $orderDto->getOrderId());
        $expectedOrderEntity = $this->makeExpectedOrderEntity($orderDto);
        self::assertEquals($expectedOrderEntity, $orderEntity);

        $rejectedMessages = $this->getRejectedExportOrderMessages();
        assertEmpty($rejectedMessages);
    }

    /**
     * @throws Exception
     */
    public function testCreateDiscountedOrder(): void
    {
        $kernel = self::bootKernel();

        $featureManager = $this->createMock(FeatureManagerInterface::class);
        $featureManager
            ->method('isActive')
            ->willReturnMap(
                [
                    [FeatureManager::IS_ENABLED_MONOLITH_KAFKA, null, false],
                    [FeatureManager::IS_ENABLED_DELIVERY_TO_CUSTOMER, null, false],
                    [FeatureManager::IS_ENABLED_DISTRIBUTORS_RESERVES, null, false],
                    [FeatureManager::IS_ENABLED_APPLY_ITEMS_DISCOUNT, null, true],
                ]
            );

        self::getContainer()->set('flagception.manager.feature_manager', $featureManager);

        $cartDto = (new CartDto())
            ->addItem(
                (new CartItemDto())
                    ->setProductArticle('1000000')
                    ->setProductBarcode('product-barcode')
                    ->setProductVendorCode('product-vendor-code')
                    ->setProductName('Товар')
                    ->setReserved(0)
                    ->setQuantity(1)
                    ->setPrice(100)
            );

        $orderDto = (new OrderDtoGenerator())
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_CREATED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_CREATED)
            ->setCartDto($cartDto);

        $discountRatio = 0.9;

        $expectedRequestDto = $this->makeExpectedRequestDtoForCreatedStatus($orderDto, false, $discountRatio);
        $this->initPROVIDERClientMock($orderDto, $expectedRequestDto);

        $messageStub = $this->makeMessageStubForCreatedOrder($orderDto);
        $messageStub->setSumPay($messageStub->getSumPay() * $discountRatio);
        $this->sendMessageToTransport($messageStub);
        $this->executeConsume($kernel);

        $rejectedMessages = $this->getRejectedExportOrderMessages();
        assertEmpty($rejectedMessages);
    }

    /**
     * Тест обработки сообщения из "ИМ" с обновлением статуса заказа.
     *
     * @dataProvider updatedOrderProvider
     *
     * @throws Throwable
     */
    public function testUpdatedOrder(OrderDto $orderDto, bool $isEnabledDeliveryToCustomer, bool $isEnabledDistributorReserves): void
    {
        $kernel = self::bootKernel();

        $this->initOrderFixture($orderDto);
        $this->initOrderReservesFixture($orderDto);

        $this->initMockFeatureManager($isEnabledDeliveryToCustomer, $isEnabledDistributorReserves);

        $expectedRequestDto = $this->makeExpectedRequestDtoForUpdateOrder($orderDto);
        $this->initPROVIDERClientMock($orderDto, $expectedRequestDto);

        $messageStub = $this->makeMessageStubForUpdatedOrder($orderDto);
        $this->sendMessageToTransport($messageStub);
        $this->executeConsume($kernel);

        $orderEntity = $this->findOrderEntity((int) $orderDto->getOrderId());
        if (in_array($orderDto->getImStatus(), self::IM_STATUSES_FINISHED_ORDERS, true)) {
            self::assertNull($orderEntity);
        } else {
            self::assertNotEmpty($orderEntity);
        }

        $expectedChangeRemainQuantityMessages = $this->makeExpectedChangeRemainQuantityMessages($orderDto, $isEnabledDistributorReserves, -1);
        $producedChangeRemainQuantityMessages = $this->getProducedChangeRemainQuantityMessages();
        self::assertEmpty(array_udiff($expectedChangeRemainQuantityMessages, $producedChangeRemainQuantityMessages, [$this, 'compareChangeRemainQuantityArrays']));

        $rejectedMessages = $this->getRejectedExportOrderMessages();
        assertEmpty($rejectedMessages);
    }

    /**
     * @throws Exception
     */
    public function createdOrderProvider(): iterable
    {
        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_CREATED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_CREATED);

        yield 'created--is_disabled_delivery_to_customer--disabled_distributor_reserves' => [$orderDto, false, false];
        yield 'created--is_enabled_delivery_to_customer--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'created--is_disabled_delivery_to_customer--enabled_distributor_reserves' => [$orderDto, false, true];
        yield 'created--is_enabled_delivery_to_customer--enabled_distributor_reserves' => [$orderDto, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_CREATED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_CREATED);

        $orderDto->getCartDto()->addItem($orderDto->getCartDto()->getItems()[0]);

        yield 'created--duplicated-basket-items--is_disabled_delivery_to_customer--disabled_distributor_reserves' => [$orderDto, false, false];

        $orderDto = (new OrderDtoGenerator())
            ->withAddressDelivery()
            ->withCart()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_CREATED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_CREATED);

        yield 'created--is_enabled_delivery_to_customer--with_address_delivery--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'created--is_enabled_delivery_to_customer--with_address_delivery--enabled_distributor_reserves' => [$orderDto, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withAddressDelivery()
            ->withCart()
            ->withRecipe()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_CREATED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_CREATED);

        yield 'created--is_enabled_delivery_to_customer--with_address_delivery--with_recipe--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'created--is_enabled_delivery_to_customer--with_address_delivery--with_recipe--enabled_distributor_reserves' => [$orderDto, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withDateOrderExecution()
            ->withCart()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_CREATED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_CREATED);

        yield 'created--is_disabled_delivery_to_customer--with_date_order_execution--disabled_distributor_reserves' => [$orderDto, false, false];
        yield 'created--is_enabled_delivery_to_customer--with_date_order_execution--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'created--is_disabled_delivery_to_customer--with_date_order_execution--enabled_distributor_reserves' => [$orderDto, false, true];
        yield 'created--is_enabled_delivery_to_customer--with_date_order_execution--enabled_distributor_reserves' => [$orderDto, true, true];
    }

    /**
     * @throws Exception
     */
    public function updatedOrderProvider(): iterable
    {
        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_CANCELLED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_CANCELED);

        yield 'cancelled--is_disabled_delivery_to_customer--disabled_distributor_reserves' => [$orderDto, false, false];
        yield 'cancelled--is_enabled_delivery_to_customer--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'cancelled--is_disabled_delivery_to_customer--enabled_distributor_reserves' => [$orderDto, false, true];
        yield 'cancelled--is_enabled_delivery_to_customer--enabled_distributor_reserves' => [$orderDto, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withDateOrderExecution()
            ->withCart()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_CANCELLED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_CANCELED);

        yield 'cancelled--is_enabled_delivery_to_customer--with_date_order_execution--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'cancelled--is_enabled_delivery_to_customer--with_date_order_execution--enabled_distributor_reserves' => [$orderDto, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withDateOrderExecution()
            ->withCart()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_NOT_REDEEMED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_CANCELED);

        yield 'not_redeemed--is_enabled_delivery_to_customer--with_date_order_execution--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'not_redeemed--is_enabled_delivery_to_customer--with_date_order_execution--enabled_distributor_reserves' => [$orderDto, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_FINISHED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_COMPLETED);

        yield 'finished--is_disabled_delivery_to_customer--disabled_distributor_reserves' => [$orderDto, false, false];
        yield 'finished--is_enabled_delivery_to_customer--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'finished--is_disabled_delivery_to_customer--enabled_distributor_reserves' => [$orderDto, false, true];
        yield 'finished--is_enabled_delivery_to_customer--enabled_distributor_reserves' => [$orderDto, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->withAddressDelivery()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_READY_TO_COURIER)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_READY);

        yield 'ready_to_courier--is_enabled_delivery_to_customer--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'ready_to_courier--is_enabled_delivery_to_customer--enabled_distributor_reserves' => [$orderDto, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->withAddressDelivery()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_LOST_BY_COURIER)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_LOST_BY_COURIER);

        yield 'lost_by_courier--is_enabled_delivery_to_customer--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'lost_by_courier--is_enabled_delivery_to_customer--enabled_distributor_reserves' => [$orderDto, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->withAddressDelivery()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_WAITING_OF_RETURN)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_WAITING_OF_RETURN);

        yield 'waiting_of_return--is_enabled_delivery_to_customer--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'waiting_of_return--is_enabled_delivery_to_customer--enabled_distributor_reserves' => [$orderDto, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->withAddressDelivery()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_NON_PURCHASE_ACCEPTED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_NON_PURCHASE_ACCEPTED);

        yield 'non_purchase_accepted--is_enabled_delivery_to_customer--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'non_purchase_accepted--is_enabled_delivery_to_customer--enabled_distributor_reserves' => [$orderDto, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->withAddressDelivery()
            ->withAcceptCode()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_WAITING_OF_COURIER)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_WAITING_OF_COURIER);

        yield 'waiting_of_courier--is_enabled_delivery_to_customer--disabled_distributor_reserves' => [$orderDto, true, false];
        yield 'waiting_of_courier--is_enabled_delivery_to_customer--enabled_distributor_reserves' => [$orderDto, true, true];
    }

    private function findOrderEntity(int $orderId): ?Order
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        return $entityManager->getRepository(Order::class)->find($orderId);
    }

    private function findRemainReserveEntity(string $storeId, string $article, int $quantity): ?RemainReserve
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        return $entityManager->getRepository(RemainReserve::class)->findOneBy([
            'storeId' => $storeId,
            'article' => $article,
            'quantity' => $quantity,
        ]);
    }

    private function findOrderReserveEntity(string $orderId, string $article, int $quantity): ?OrderReserve
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        return $entityManager->getRepository(OrderReserve::class)->findOneBy([
            'orderId' => $orderId,
            'article' => $article,
            'quantity' => $quantity,
        ]);
    }

    private function serializeToJson(array $data): string
    {
        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get('serializer');

        return $serializer->serialize($data, 'json');
    }

    private function makeMessageStubForCreatedOrder(OrderDto $orderDto): ExportOrder
    {
        $items = [];

        $sumPay = 0;
        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $item = new ExportOrderItem(
                $cartItemDto->getProductArticle(),
                $cartItemDto->getQuantity(),
                $cartItemDto->getQuantity(),
                $cartItemDto->getPrice()
            );
            $item
                ->setVendorCode($cartItemDto->getProductVendorCode())
                ->setBarcode($cartItemDto->getProductBarcode());

            if ($orderDto->getAddressDelivery()) {
                $item->setRecipeId($cartItemDto->getRecipeId());
            }

            $items[] = $item;
            $sumPay += $cartItemDto->getQuantity() * $cartItemDto->getPrice();
        }

        $message = new ExportOrder(
            $orderDto->getOrderId(),
            ImOrderStatus::STATUS_CREATED,
            OrderTypeInterface::TYPE_PROVIDER,
            $orderDto->getDateOrder(),
            $orderDto->getPhone()
        );
        $message
            ->setRequestId($orderDto->getRequestId())
            ->setComment($orderDto->getComment())
            ->setTypePay(0)
            ->setSumPay($sumPay)
            ->setDivision($orderDto->getStoreId())
            ->setDateProviding($orderDto->getDateProviding())
            ->setDivisionPost($orderDto->getDivisionPost())
            ->setPartnerName($orderDto->getPartnerName())
            ->setItems($items);
        if ($orderDto->getAddressDelivery()) {
            $message->setAddressDelivery($orderDto->getAddressDelivery());
        }
        if ($orderDto->getDateOrderExecution()) {
            $message->setDateOrderExecution($orderDto->getDateOrderExecution());
        }

        return $message;
    }

    private function makeMessageStubForUpdatedOrder(OrderDto $orderDto): ExportOrder
    {
        $message = new ExportOrder(
            $orderDto->getOrderId(),
            $orderDto->getImStatus(),
            OrderTypeInterface::TYPE_PROVIDER,
            $orderDto->getDateOrder(),
            $orderDto->getPhone()
        );
        $message
            ->setRequestId($orderDto->getRequestId());
        if ($orderDto->getAcceptCode()) {
            $message->setAcceptCode($orderDto->getAcceptCode());
        }

        return $message;
    }

    private function makeExpectedRequestDtoForCreatedStatus(OrderDto $orderDto, bool $isDeliveryToCustomer, float $discountRatio = 1.0): RequestDto
    {
        /**
         * Порядок добавления элементов в массив важен, т.к. далее будут сравниваться json строк.
         */
        $sumPay = 0;
        $rows = [];
        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $row = [];
            $row['Quantity'] = $cartItemDto->getQuantity();
            $row['Reserved'] = $cartItemDto->getQuantity();
            $row['Sum'] = $cartItemDto->getQuantity() * $cartItemDto->getPrice() * $discountRatio;
            $row['Price'] = $cartItemDto->getPrice() * $discountRatio;
            if ($isDeliveryToCustomer && $cartItemDto->getRecipeId()) {
                $row['Recipe'] = $cartItemDto->getRecipeId();
            }
            $row['Product'] = [
                'Code' => $cartItemDto->getProductArticle(),
                'VendorCode' => $cartItemDto->getProductVendorCode(),
                'Barcode' => $cartItemDto->getProductBarcode(),
            ];

            $rows[] = $row;
            $sumPay += $cartItemDto->getQuantity() * $cartItemDto->getPrice() * $discountRatio;
        }

        $body = [];
        $body['Id'] = $orderDto->getRequestId();
        $body['Number'] = $orderDto->getOrderId();
        $body['Status'] = PROVIDEROrderStatus::STATUS_CREATED;
        $body['TypeOrder'] = OrderTypeInterface::TYPE_PROVIDER;
        $body['DateOrder'] = $orderDto->getDateOrder();
        $body['Division'] = $orderDto->getStoreId();
        $body['Phone'] = $orderDto->getPhone();
        $body['DateProvidingOrder'] = $orderDto->getDateProviding();
        if ($orderDto->getDateOrderExecution()) {
            $body['DateOrderExecution'] = $orderDto->getDateOrderExecution();
        }
        if ($isDeliveryToCustomer) {
            $body['AddressDelivery'] = $orderDto->getAddressDelivery();
        }
        $body['TypePay'] = 0;
        $body['DivisionPost'] = $orderDto->getDivisionPost();
        $body['SumPay'] = $sumPay;
        $body['Comment'] = $orderDto->getComment();
        $body['Partner'] = $orderDto->getPartnerName();

        $body['Rows'] = $rows;

        return (new RequestDto())
            ->setMethod(Request::METHOD_PUT)
            ->setUrl("http://PROVIDER/e-shop/v2/orders/{$orderDto->getOrderId()}")
            ->setHeaders([])
            ->setBody($this->serializeToJson($body));
    }

    private function makeExpectedRequestDtoForUpdateOrder(OrderDto $orderDto): RequestDto
    {
        $body = [
            'Id' => $orderDto->getRequestId(),
            'Number' => $orderDto->getOrderId(),
            'Status' => $orderDto->getPROVIDERStatus(),
            'TypeOrder' => OrderTypeInterface::TYPE_PROVIDER,
        ];
        if ($orderDto->getAcceptCode()) {
            $body['AcceptCode'] = $orderDto->getAcceptCode();
        }

        return (new RequestDto())
            ->setMethod(Request::METHOD_PATCH)
            ->setUrl("http://PROVIDER/e-shop/v2/orders/{$orderDto->getOrderId()}")
            ->setHeaders([])
            ->setBody(
                $this->serializeToJson($body)
            );
    }

    private function initMockFeatureManager(bool $isEnabledDeliveryToCustomer, bool $isEnabledDistributorReserves): void
    {
        $featureManager = $this->createMock(FeatureManagerInterface::class);
        $featureManager
            ->method('isActive')
            ->willReturnMap(
                [
                    [FeatureManager::IS_ENABLED_MONOLITH_KAFKA, null, false],
                    [FeatureManager::IS_ENABLED_DELIVERY_TO_CUSTOMER, null, $isEnabledDeliveryToCustomer],
                    [FeatureManager::IS_ENABLED_DISTRIBUTORS_RESERVES, null, $isEnabledDistributorReserves],
                    [FeatureManager::IS_ENABLED_APPLY_ITEMS_DISCOUNT, null, false],
                ]
            );

        self::getContainer()->set('flagception.manager.feature_manager', $featureManager);
    }

    private function initPROVIDERClientMock(OrderDto $orderDto, RequestDto $requestDto): void
    {
        $responseStub = $this->createStub(ResponseInterface::class);
        $responseStub
            ->method('getContent')
            ->with(true)
            ->willReturn(
                $this->serializeToJson([
                    'Id' => Uuid::v4()->toRfc4122(),
                    'OrderId' => $orderDto->getOrderId(),
                    'Message' => 'Помещен в очередь',
                ])
            );

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                $requestDto->getMethod(),
                $requestDto->getUrl(),
                [
                    'headers' => $requestDto->getHeaders(),
                    'body' => $requestDto->getBody(),
                ]
            )
            ->willReturn($responseStub);

        self::getContainer()->set('test.PROVIDER.client', $httpClient);
    }

    private function sendMessageToTransport(ExportOrder $message): void
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.v2_export_orders');
        $transport->send(Envelope::wrap($message));
    }

    private function executeConsume(KernelInterface $kernel): void
    {
        $application = new Application($kernel);
        $command = $application->find('messenger:consume');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'receivers' => ['v2_export_orders'],
            '--limit' => '1',
        ]);

        $commandTester->assertCommandIsSuccessful();
    }

    private function makeExpectedOrderEntity(OrderDto $orderDto): Order
    {
        return (new Order())
            ->setId((int) $orderDto->getOrderId())
            ->setIsDeliveryToCustomer((bool) $orderDto->getAddressDelivery())
            ->setIsDistributor((bool) $orderDto->getDateOrderExecution());
    }

    private function makeExpectedChangeRemainQuantityMessages(OrderDto $orderDto, bool $isEnabledDistributorReserves, int $signReserve = 1): array
    {
        if (!$isEnabledDistributorReserves && $orderDto->getDateOrderExecution()) {
            return [];
        }

        if ($signReserve < 0) {
            if (!$orderDto->getDateOrderExecution()) {
                return [];
            }

            $imStatuses = [
                ImOrderStatus::STATUS_CANCELLED,
                ImOrderStatus::STATUS_NOT_REDEEMED,
            ];

            if (!in_array($orderDto->getImStatus(), $imStatuses, true)) {
                return [];
            }
        }

        $expectedChangeRemainQuantityMessages = [];

        // Группировка по артикулу
        $cartItemsByArticle = [];
        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $cartItemsByArticle[$cartItemDto->getProductArticle()][] = $cartItemDto;
        }

        foreach ($cartItemsByArticle as $cartItems) {
            $cartItemDto = $cartItems[0];
            $quantity = 0;
            foreach ($cartItems as $item) {
                $quantity += $item->getQuantity();
            }
            $expectedChangeRemainQuantityMessages[] = (new ChangeRemainQuantity(
                $orderDto->getStoreId(),
                $cartItemDto->getProductArticle(),
                $signReserve * $quantity,
            ))
                ->setIsDistributor((bool) $orderDto->getDateOrderExecution());
        }

        return $expectedChangeRemainQuantityMessages;
    }

    private function initOrderFixture(OrderDto $orderDto): void
    {
        $container = self::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        $orderEntity = (new Order())
            ->setId((int) $orderDto->getOrderId())
            ->setIsDeliveryToCustomer((bool) $orderDto->getAddressDelivery())
            ->setIsDistributor((bool) $orderDto->getDateOrderExecution());
        $entityManager->persist($orderEntity);
        $entityManager->flush();
    }

    private function initOrderReservesFixture(OrderDto $orderDto): void
    {
        $container = self::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $orderReserveEntity = (new OrderReserve())
                ->setOrderId($orderDto->getOrderId())
                ->setStoreId($orderDto->getStoreId())
                ->setQuantity($cartItemDto->getQuantity())
                ->setArticle($cartItemDto->getProductArticle());

            $entityManager->persist($orderReserveEntity);
        }
        $entityManager->flush();
    }

    private function getProducedChangeRemainQuantityMessages(): array
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.v2_change_remain_quantity');

        $producedChangeRemainQuantityMessages = [];
        foreach ($transport->getSent() as $producedChangeRemainQuantityEnvelope) {
            $producedChangeRemainQuantityMessages[] = $producedChangeRemainQuantityEnvelope->getMessage();
        }

        return $producedChangeRemainQuantityMessages;
    }

    public function compareChangeRemainQuantityArrays(ChangeRemainQuantity $a, ChangeRemainQuantity $b): int
    {
        $aKey = $a->getReserve() . $a->getStoreId() . $a->getArticle() . $a->getIsDistributor();
        $bKey = $b->getReserve() . $b->getStoreId() . $b->getArticle() . $b->getIsDistributor();

        return $aKey <=> $bKey;
    }

    private function getRejectedExportOrderMessages(): array
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.v2_export_orders');

        return $transport->getRejected();
    }
}
