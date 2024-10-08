<?php

namespace App\Tests\Controller\PROVIDER;

use App\Entity\Order;
use App\Manager\FeatureManager;
use App\Message\OrderReserveRemoving;
use App\Repository\OrderRepository;
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
use test1\Message\V2\ImportOrder;
use test1\Message\V2\ImportOrderItem;
use test1\Message\V2\ImportOrderStatus;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Serializer\SerializerInterface;

class OrdersControllerUpdateStatusTest extends WebTestCase
{
    private const PROVIDER_STATUSES_FOR_REMOVING_RESERVE = [
        PROVIDEROrderStatus::STATUS_READY,
        PROVIDEROrderStatus::STATUS_PART_READY,
    ];

    private const PROVIDER_STATUSES_DISTRIBUTORS_FOR_REMOVING_RESERVE = [
        PROVIDEROrderStatus::STATUS_CANCELED,
    ];

    private const IM_STATUSES_FOR_ORDER_ITEMS = [
        ImOrderStatus::STATUS_CONFIRMATION,
        ImOrderStatus::STATUS_READY_TO_COURIER,
    ];

    /**
     * Тест запроса из УАС на смену статуса заказа.
     *
     * @dataProvider updatedOrderProvider
     */
    public function testPROVIDERChangingStatus(
        OrderDto $orderDto,
        bool $isEnabledDeliveryToCustomer,
        bool $withOrderEntity,
        bool $isMultipleStatuses
    ): void {
        $client = static::createClient();

        if ($withOrderEntity) {
            $this->makeAndSaveOrderEntityToDatabase($orderDto);
        }
        $requestDto = $this->makeRequestDto($orderDto, $isMultipleStatuses);

        $this->initMockFeatureManager($isEnabledDeliveryToCustomer);

        $this->executeHttpRequest($client, $requestDto);
        self::assertResponseIsSuccessful();

        $expectedImportMessage = $this->makeExpectedImportMessage($orderDto, $isMultipleStatuses);
        $producedImportMessage = $this->getProducedImportMessage();
        self::assertEquals($expectedImportMessage, $producedImportMessage);

        $producedReserveRemovingMessage = $this->getProducedReserveRemovingMessage();
        $expectedReserveRemovingMessage = $this->makeExpectedOrderReserveRemovingMessage($orderDto, $isMultipleStatuses);
        self::assertEquals($expectedReserveRemovingMessage, $producedReserveRemovingMessage);
    }

    /**
     * Тест запроса из УАС на смену статуса заказа "Аннулирован".
     *
     * @dataProvider nullifiedOrderProvider
     */
    public function testPROVIDERChangingStatusToNullified(
        OrderDto $orderDto,
        bool $isEnabledDeliveryToCustomer,
        bool $isMultipleStatuses
    ): void {
        $client = static::createClient();

        $requestDto = $this->makeRequestDto($orderDto, $isMultipleStatuses);

        $this->initMockFeatureManager($isEnabledDeliveryToCustomer);

        $this->executeHttpRequest($client, $requestDto);
        self::assertResponseIsSuccessful();

        $producedImportMessage = $this->getProducedImportMessage();
        self::assertNull($producedImportMessage);
    }

    /**
     * Тест запроса из УАС на смену статуса заказа "Частично готов к выдаче"
     *  - для заказа, которого нет в таблице orders
     *  - при выключенном фичетогле IS_ENABLED_provCY_DELIVERY_TO_CUSTOMER
     *  - при пустых полях Sid, RecipeConfirm, MarkingCodes.
     *
     * @throws Exception
     */
    public function testPROVIDERChangingStatusToPartReadyWithRecipeEmptyFields(): void
    {
        $client = static::createClient();

        $cartItemDto = (new CartItemDto())
            ->setProductArticle((string) random_int(1000000, 9999999))
            ->setQuantity(2)
            ->setReserved(2)
            ->setPrice(random_int(10000, 1000000) / 100)
            ->setProductBarcode((string) random_int(1000000000, 9999999999))
            ->setProductVendorCode((string) random_int(1000000000, 9999999999));

        $orderDto = (new OrderDtoGenerator())->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_PART_READY)
            ->setImStatus(ImOrderStatus::STATUS_CONFIRMATION)
            ->setCartDto(
                (new CartDto())->addItem($cartItemDto)
            );

        $requestDto = (new RequestDto())
            ->setMethod(Request::METHOD_PATCH)
            ->setUrl('/api/PROVIDER/v2/orders/status')
            ->setBody(
                $this->serializeToJson([
                    'Status' => PROVIDEROrderStatus::STATUS_PART_READY,
                    'TypeOrder' => OrderTypeInterface::TYPE_PROVIDER,
                    'Number' => $orderDto->getOrderId(),
                    'TimeEdit' => $orderDto->getPROVIDERTimeEdit(),
                    'UserEdit' => $orderDto->getPROVIDERUserEdit(),
                    'Id' => $orderDto->getRequestId(),
                    'Sid' => '',
                    'Rows' => [
                        [
                            'Product' => [
                                'Code' => $cartItemDto->getProductArticle(),
                            ],
                            'Quantity' => $cartItemDto->getQuantity(),
                            'Reserved' => $cartItemDto->getReserved(),
                            'Sum' => $cartItemDto->getQuantity() * $cartItemDto->getPrice(),
                            'RecipeConfirm' => '',
                            'MarkingCodes' => [],
                        ],
                    ],
                ])
            );

        $this->initMockFeatureManager(false);

        $this->executeHttpRequest($client, $requestDto);
        self::assertResponseIsSuccessful();

        $expectedMessage = $this->makeExpectedImportOrderMessage($orderDto);
        $producedMessage = $this->getProducedImportMessage();
        self::assertEquals($expectedMessage, $producedMessage);
    }

    private function makeRequestDto(OrderDto $orderDto, bool $isMultipleStatuses): RequestDto
    {
        if ($isMultipleStatuses) {
            return $this->makeRequestDtoMultiple($orderDto);
        }

        if (
            in_array($orderDto->getPROVIDERStatus(), [
                PROVIDEROrderStatus::STATUS_READY,
                PROVIDEROrderStatus::STATUS_PART_READY,
            ], true)
        ) {
            return $this->makeRequestDtoWithRows($orderDto);
        }

        return $this->makeRequestDtoDefault($orderDto);
    }

    private function makeRequestDtoDefault(OrderDto $orderDto): RequestDto
    {
        return (new RequestDto())
            ->setMethod(Request::METHOD_PATCH)
            ->setUrl('/api/PROVIDER/v2/orders/status')
            ->setBody(
                $this->serializeToJson(
                    [
                        'Status' => $orderDto->getPROVIDERStatus(),
                        'TypeOrder' => OrderTypeInterface::TYPE_PROVIDER,
                        'Number' => $orderDto->getOrderId(),
                        'TimeEdit' => $orderDto->getPROVIDERTimeEdit(),
                        'UserEdit' => $orderDto->getPROVIDERUserEdit(),
                        'Id' => $orderDto->getRequestId(),
                    ]
                )
            );
    }

    private function makeRequestDtoMultiple(OrderDto $orderDto): RequestDto
    {
        return (new RequestDto())
            ->setMethod(Request::METHOD_PUT)
            ->setUrl('/api/PROVIDER/v2/orders/status')
            ->setBody(
                $this->serializeToJson(
                    [
                        'Id' => $orderDto->getRequestId(),
                        'Time' => date('d.m.Y H:i:s'),
                        'Orders' => [
                            [
                                'Order' => $orderDto->getOrderId(),
                                'Status' => $orderDto->getPROVIDERStatus(),
                                'Date' => $orderDto->getPROVIDERTimeEdit(),
                            ],
                        ],
                    ]
                )
            );
    }

    private function makeRequestDtoWithRows(OrderDto $orderDto): RequestDto
    {
        $rows = [];
        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $row = [
                'Product' => [
                    'Code' => $cartItemDto->getProductArticle(),
                ],
                'Quantity' => $cartItemDto->getQuantity(),
                'Reserved' => $cartItemDto->getReserved(),
                'Sum' => $cartItemDto->getQuantity() * $cartItemDto->getPrice(),
            ];
            if ($cartItemDto->getRecipeConfirm()) {
                $row['RecipeConfirm'] = $cartItemDto->getRecipeConfirm();
            }
            if ($cartItemDto->getMarkingCodes()) {
                $row['MarkingCodes'] = $cartItemDto->getMarkingCodes();
            }

            $rows[] = $row;
        }

        $body = [
            'Status' => $orderDto->getPROVIDERStatus(),
            'TypeOrder' => OrderTypeInterface::TYPE_PROVIDER,
            'Number' => $orderDto->getOrderId(),
            'TimeEdit' => $orderDto->getPROVIDERTimeEdit(),
            'UserEdit' => $orderDto->getPROVIDERUserEdit(),
            'Id' => $orderDto->getRequestId(),
            'Rows' => $rows,
        ];

        if ($orderDto->getSid()) {
            $body['Sid'] = $orderDto->getSid();
        }

        return (new RequestDto())
            ->setMethod(Request::METHOD_PATCH)
            ->setUrl('/api/PROVIDER/v2/orders/status')
            ->setHeaders([])
            ->setBody(
                $this->serializeToJson($body)
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

    private function getProducedImportMessage(): ?object
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

    private function makeExpectedImportMessage(OrderDto $orderDto, bool $isMultipleStatuses): ImportOrder|ImportOrderStatus|null
    {
        if (in_array($orderDto->getImStatus(), self::IM_STATUSES_FOR_ORDER_ITEMS, true)) {
            if ($isMultipleStatuses) {
                return null;
            }

            return $this->makeExpectedImportOrderMessage($orderDto);
        }

        return $this->makeExpectedImportOrderStatusMessage($orderDto, $isMultipleStatuses);
    }

    private function makeExpectedImportOrderStatusMessage(OrderDto $orderDto, bool $isMultipleStatuses): ImportOrderStatus
    {
        $message = new ImportOrderStatus(
            $orderDto->getOrderId(),
            $orderDto->getImStatus(),
            $isMultipleStatuses ? null : $orderDto->getPROVIDERUserEdit(),
            $isMultipleStatuses ? null : $orderDto->getPROVIDERTimeEdit()
        );
        $message->setRequestId($orderDto->getRequestId());

        return $message;
    }

    private function makeExpectedImportOrderMessage(OrderDto $orderDto): ImportOrder
    {
        $items = [];
        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $item = new ImportOrderItem(
                $cartItemDto->getQuantity(),
                $cartItemDto->getQuantity() * $cartItemDto->getPrice(),
                $cartItemDto->getProductArticle(),
                $cartItemDto->getReserved()
            );
            if ($cartItemDto->getMarkingCodes()) {
                $item->setMarkingCodes($cartItemDto->getMarkingCodes());
            }
            if ($cartItemDto->getRecipeConfirm()) {
                $item->setRecipeConfirm($cartItemDto->getRecipeConfirm());
            }
            $items[] = $item;
        }

        $message = new ImportOrder(
            $orderDto->getOrderId(),
            $orderDto->getImStatus(),
            $items,
            $orderDto->getPROVIDERUserEdit(),
            $orderDto->getPROVIDERTimeEdit()
        );
        $message
            ->setRequestId($orderDto->getRequestId());
        if ($orderDto->getSid()) {
            $message->setSid($orderDto->getSid());
        }

        return $message;
    }

    private function makeExpectedOrderReserveRemovingMessage(OrderDto $orderDto, bool $isMultipleStatuses): ?OrderReserveRemoving
    {
        if ($isMultipleStatuses) {
            return null;
        }

        $statusesForRemovingReserve = self::PROVIDER_STATUSES_FOR_REMOVING_RESERVE;
        if ($orderDto->getDateOrderExecution()) {
            $statusesForRemovingReserve = self::PROVIDER_STATUSES_DISTRIBUTORS_FOR_REMOVING_RESERVE;
        }

        if (!in_array($orderDto->getPROVIDERStatus(), $statusesForRemovingReserve, true)) {
            return null;
        }

        return new OrderReserveRemoving($orderDto->getOrderId());
    }

    private function makeAndSaveOrderEntityToDatabase(OrderDto $orderDto): void
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

    private function initMockFeatureManager(bool $isEnabledDeliveryToCustomer): void
    {
        $featureManager = $this->createMock(FeatureManagerInterface::class);
        $featureManager
            ->method('isActive')
            ->willReturnMap(
                [
                    [FeatureManager::IS_ENABLED_MONOLITH_KAFKA, null, false],
                    [FeatureManager::IS_ENABLED_DELIVERY_TO_CUSTOMER, null, $isEnabledDeliveryToCustomer],
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

    /**
     * @throws Exception
     */
    public function updatedOrderProvider(): iterable
    {
        $orderDto = (new OrderDtoGenerator())
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_ASSEMBLING)
            ->setImStatus(ImOrderStatus::STATUS_ASSEMBLING);

        yield 'assembling--is_disabled_delivery_to_customer--not_order_entity' => [$orderDto, false, false, false];
        yield 'assembling--is_enabled_delivery_to_customer--not_order_entity' => [$orderDto, true, false, false];
        yield 'assembling--is_disabled_delivery_to_customer--with_order_entity' => [$orderDto, false, true, false];
        yield 'assembling--is_enabled_delivery_to_customer--with_order_entity' => [$orderDto, true, true, false];
        yield 'assembling--is_disabled_delivery_to_customer--not_order_entity--is-multiple_statuses' => [$orderDto, false, false, true];
        yield 'assembling--is_enabled_delivery_to_customer--not_order_entity--is-multiple_statuses' => [$orderDto, true, false, true];
        yield 'assembling--is_disabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, false, true, true];
        yield 'assembling--is_enabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_WAIT_MDLP)
            ->setImStatus(ImOrderStatus::STATUS_WAIT_MDLP);

        yield 'wait_mdlp--is_disabled_delivery_to_customer--not_order_entity' => [$orderDto, false, false, false];
        yield 'wait_mdlp--is_enabled_delivery_to_customer--not_order_entity' => [$orderDto, true, false, false];
        yield 'wait_mdlp--is_disabled_delivery_to_customer--with_order_entity' => [$orderDto, false, true, false];
        yield 'wait_mdlp--is_enabled_delivery_to_customer--with_order_entity' => [$orderDto, true, true, false];
        yield 'wait_mdlp--is_disabled_delivery_to_customer--not_order_entity--is-multiple_statuses' => [$orderDto, false, false, true];
        yield 'wait_mdlp--is_enabled_delivery_to_customer--not_order_entity--is-multiple_statuses' => [$orderDto, true, false, true];
        yield 'wait_mdlp--is_disabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, false, true, true];
        yield 'wait_mdlp--is_enabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_RECIPE_COMPLETED)
            ->setImStatus(ImOrderStatus::STATUS_RECIPE_COMPLETED);

        yield 'recipe_completed--is_enabled_delivery_to_customer--with_order_entity' => [$orderDto, true, true, false];
        yield 'recipe_completed--is_enabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_NON_PURCHASE_PARTIALLY_ACCEPTED)
            ->setImStatus(ImOrderStatus::STATUS_NON_PURCHASE_PARTIALLY_ACCEPTED);

        yield 'non_purchase_partially_accepted--is_enabled_delivery_to_customer--with_order_entity' => [$orderDto, true, true, false];
        yield 'non_purchase_partially_accepted--is_enabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_NON_PURCHASE_ACCEPTED)
            ->setImStatus(ImOrderStatus::STATUS_NON_PURCHASE_ACCEPTED);

        yield 'non_purchase_accepted--is_enabled_delivery_to_customer--with_order_entity' => [$orderDto, true, true, false];
        yield 'non_purchase_accepted--is_enabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_TRANSFERRED_TO_COURIER)
            ->setImStatus(ImOrderStatus::STATUS_TRANSFERRED_TO_COURIER);

        yield 'transferred_to_courier--is_enabled_delivery_to_customer--with_order_entity' => [$orderDto, true, true, false];
        yield 'transferred_to_courier--is_enabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_READY)
            ->setImStatus(ImOrderStatus::STATUS_LET_OUT);

        yield 'ready--is_disabled_delivery_to_customer--not_order_entity' => [$orderDto, false, false, false];
        yield 'ready--is_enabled_delivery_to_customer--not_order_entity' => [$orderDto, true, false, false];
        yield 'ready--is_disabled_delivery_to_customer--with_order_entity' => [$orderDto, false, true, false];
        yield 'ready--is_enabled_delivery_to_customer--with_order_entity' => [$orderDto, true, true, false];
        yield 'ready--is_disabled_delivery_to_customer--not_order_entity--is-multiple_statuses' => [$orderDto, false, false, true];
        yield 'ready--is_enabled_delivery_to_customer--not_order_entity--is-multiple_statuses' => [$orderDto, true, false, true];
        yield 'ready--is_disabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, false, true, true];
        yield 'ready--is_enabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withAddressDelivery()
            ->withCart()
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_READY)
            ->setImStatus(ImOrderStatus::STATUS_READY_TO_COURIER);

        yield 'ready--is_enabled_delivery_to_customer--with_address_delivery--with_order_entity' => [$orderDto, true, true, false];
        yield 'ready--is_enabled_delivery_to_customer--with_address_delivery--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withDateOrderExecution()
            ->withCart()
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_READY)
            ->setImStatus(ImOrderStatus::STATUS_LET_OUT);

        yield 'ready--is_disabled_delivery_to_customer--with_date_order_execution--with_order_entity' => [$orderDto, false, true, false];
        yield 'ready--is_enabled_delivery_to_customer--with_date_order_execution--with_order_entity' => [$orderDto, true, true, false];
        yield 'ready--is_disabled_delivery_to_customer--with_date_order_execution--with_order_entity--is-multiple_statuses' => [$orderDto, false, true, true];
        yield 'ready--is_enabled_delivery_to_customer--with_date_order_execution--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_ON_TRADES)
            ->setImStatus(ImOrderStatus::STATUS_ON_TRADES);

        yield 'on_trades--is_disabled_delivery_to_customer--with_order_entity' => [$orderDto, false, true, false];
        yield 'on_trades--is_enabled_delivery_to_customer--with_order_entity' => [$orderDto, true, true, false];
        yield 'on_trades--is_disabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, false, true, true];
        yield 'on_trades--is_enabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_PART_READY)
            ->setImStatus(ImOrderStatus::STATUS_CONFIRMATION);

        yield 'part_ready--is_disabled_delivery_to_customer--not_order_entity' => [$orderDto, false, false, false];
        yield 'part_ready--is_enabled_delivery_to_customer--not_order_entity' => [$orderDto, true, false, false];
        yield 'part_ready--is_disabled_delivery_to_customer--with_order_entity' => [$orderDto, false, true, false];
        yield 'part_ready--is_enabled_delivery_to_customer--with_order_entity' => [$orderDto, true, true, false];
        yield 'part_ready--is_disabled_delivery_to_customer--not_order_entity--is-multiple_statuses' => [$orderDto, false, false, true];
        yield 'part_ready--is_enabled_delivery_to_customer--not_order_entity--is-multiple_statuses' => [$orderDto, true, false, true];
        yield 'part_ready--is_disabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, false, true, true];
        yield 'part_ready--is_enabled_delivery_to_customer--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withAddressDelivery()
            ->withCart()
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_PART_READY)
            ->setImStatus(ImOrderStatus::STATUS_CONFIRMATION);

        yield 'part_ready--is_enabled_delivery_to_customer--with_address_delivery--with_order_entity' => [$orderDto, true, true, false];
        yield 'part_ready--is_enabled_delivery_to_customer--with_address_delivery--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withAddressDelivery()
            ->withCart()
            ->withEmptyReserved()
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_PART_READY)
            ->setImStatus(ImOrderStatus::STATUS_CONFIRMATION);

        yield 'part_ready--is_enabled_delivery_to_customer--with_address_delivery--with_empty_reserved--with_order_entity' => [$orderDto, true, true, false];
        yield 'part_ready--is_enabled_delivery_to_customer--with_address_delivery--with_empty_reserved--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withDateOrderExecution()
            ->withCart()
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_PART_READY)
            ->setImStatus(ImOrderStatus::STATUS_CONFIRMATION);
        yield 'part_ready--is_disabled_delivery_to_customer--with_date_order_execution--with_order_entity' => [$orderDto, false, true, false];
        yield 'part_ready--is_disabled_delivery_to_customer--with_date_order_execution--with_order_entity--is-multiple_statuses' => [$orderDto, false, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->withEmptyReserved()
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_PART_READY)
            ->setImStatus(ImOrderStatus::STATUS_CONFIRMATION);

        yield 'part_ready--is_disabled_delivery_to_customer--empty_reserved--not_order_entity' => [$orderDto, false, false, false];
        yield 'part_ready--is_enabled_delivery_to_customer--empty_reserved--not_order_entity' => [$orderDto, true, false, false];
        yield 'part_ready--is_disabled_delivery_to_customer--empty_reserved--with_order_entity' => [$orderDto, false, true, false];
        yield 'part_ready--is_enabled_delivery_to_customer--empty_reserved--with_order_entity' => [$orderDto, true, true, false];
        yield 'part_ready--is_disabled_delivery_to_customer--empty_reserved--not_order_entity--is-multiple_statuses' => [$orderDto, false, false, true];
        yield 'part_ready--is_enabled_delivery_to_customer--empty_reserved--not_order_entity--is-multiple_statuses' => [$orderDto, true, false, true];
        yield 'part_ready--is_disabled_delivery_to_customer--empty_reserved--with_order_entity--is-multiple_statuses' => [$orderDto, false, true, true];
        yield 'part_ready--is_enabled_delivery_to_customer--empty_reserved--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];

        $orderDto = (new OrderDtoGenerator())
            ->withDateOrderExecution()
            ->withCart()
            ->withEmptyReserved()
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_PART_READY)
            ->setImStatus(ImOrderStatus::STATUS_CONFIRMATION);

        yield 'part_ready--is_disabled_delivery_to_customer--empty_reserved--with_date_order_execution--with_order_entity' => [$orderDto, false, true, false];
        yield 'part_ready--is_enabled_delivery_to_customer--empty_reserved--with_date_order_execution--with_order_entity' => [$orderDto, true, true, false];
        yield 'part_ready--is_disabled_delivery_to_customer--empty_reserved--with_date_order_execution--with_order_entity--is-multiple_statuses' => [$orderDto, false, true, true];
        yield 'part_ready--is_enabled_delivery_to_customer--empty_reserved--with_date_order_execution--with_order_entity--is-multiple_statuses' => [$orderDto, true, true, true];
    }

    /**
     * @throws Exception
     */
    public function nullifiedOrderProvider(): iterable
    {
        $orderDto = (new OrderDtoGenerator())
            ->generate();
        $orderDto
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_NULLIFIED);

        yield 'nullified--is_disabled_delivery_to_customer' => [$orderDto, false, false];
        yield 'nullified--is_enabled_delivery_to_customer' => [$orderDto, true, false];
        yield 'nullified--is_disabled_delivery_to_customer--is-multiple_statuses' => [$orderDto, false, true];
        yield 'nullified--is_enabled_delivery_to_customer--is-multiple_statuses' => [$orderDto, true, true];
    }
}
