<?php

namespace App\Tests\MessageHandler;

use App\Entity\Order;
use App\Entity\OrderReserve;
use App\Entity\RemainReserve;
use App\Manager\FeatureManager;
use App\Message\OrderReserveRemoving;
use App\Request\Dto\OrderStatusInterface as ImOrderStatus;
use App\Request\Dto\PROVIDER\OrderStatusInterface as PROVIDEROrderStatus;
use App\Tests\Support\Dto\OrderDto;
use App\Tests\Support\Generator\OrderDtoGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Flagception\Manager\FeatureManagerInterface;
use test1\Message\V2\ChangeRemainQuantity;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Throwable;

class OrderReserveRemovingHandlerTest extends KernelTestCase
{
    private const FIXTURE_REMAIN_RESERVE_QUANTITY = 100000;

    /**
     * @dataProvider dataProvider
     *
     * @throws Throwable
     */
    public function testOrderReserveRemoving(OrderDto $orderDto, bool $isEnabledDistributorReserves): void
    {
        $kernel = self::bootKernel();

        $this->initOrderFixture($orderDto);
        $this->initOrderReservesFixture($orderDto);
        $this->initRemainReservesFixture($orderDto);

        $this->initMockFeatureManager($isEnabledDistributorReserves);

        $messageStub = $this->makeMessageStubOrderReserveRemoving($orderDto);
        $this->sendMessageToTransport($messageStub);
        $this->executeConsume($kernel);

        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $orderReserve = $this->findOrderReserveEntity(
                $orderDto->getOrderId(),
                $cartItemDto->getProductArticle()
            );

            self::assertEmpty($orderReserve);

            $remainReserve = $this->findRemainReserveEntity(
                $orderDto->getStoreId(),
                $cartItemDto->getProductArticle()
            );

            self::assertEquals(self::FIXTURE_REMAIN_RESERVE_QUANTITY - $cartItemDto->getQuantity(), $remainReserve->getQuantity());
        }

        $expectedChangeRemainQuantityMessages = $this->makeExpectedChangeRemainQuantityMessages($orderDto);
        $producedChangeRemainQuantityMessages = $this->getProducedChangeRemainQuantityMessages();
        self::assertEmpty(array_udiff($expectedChangeRemainQuantityMessages, $producedChangeRemainQuantityMessages, [$this, 'compareChangeRemainQuantityArrays']));
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
            ->setImStatus(ImOrderStatus::STATUS_FINISHED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_COMPLETED);

        yield 'disabled_distributor_reserves' => [$orderDto, false];
        yield 'enabled_distributor_reserves' => [$orderDto, true];

        $orderDto = (new OrderDtoGenerator())
            ->withDateOrderExecution()
            ->withCart()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_FINISHED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_COMPLETED);

        yield 'with_date_order_execution--enabled_distributor_reserves' => [$orderDto, true];

        $orderDto = (new OrderDtoGenerator())
            ->withAddressDelivery()
            ->withCart()
            ->generate();
        $orderDto
            ->setImStatus(ImOrderStatus::STATUS_FINISHED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_COMPLETED);

        yield 'with_address_delivery--disabled_distributor_reserves' => [$orderDto, false];
        yield 'with_address_delivery--enabled_distributor_reserves' => [$orderDto, true];
    }

    private function findRemainReserveEntity(string $storeId, string $article): ?RemainReserve
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        return $entityManager->getRepository(RemainReserve::class)->findOneBy([
            'storeId' => $storeId,
            'article' => $article,
        ]);
    }

    private function findOrderReserveEntity(string $orderId, string $article): ?OrderReserve
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        return $entityManager->getRepository(OrderReserve::class)->findOneBy([
            'orderId' => $orderId,
            'article' => $article,
        ]);
    }

    private function makeMessageStubOrderReserveRemoving(OrderDto $orderDto): OrderReserveRemoving
    {
        return new OrderReserveRemoving($orderDto->getOrderId());
    }

    private function sendMessageToTransport(OrderReserveRemoving $message): void
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.remove_order_reserves');
        $transport->send(Envelope::wrap($message));
    }

    private function executeConsume(KernelInterface $kernel): void
    {
        $application = new Application($kernel);
        $command = $application->find('messenger:consume');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'receivers' => ['remove_order_reserves'],
            '--limit' => '1',
        ]);

        $commandTester->assertCommandIsSuccessful();
    }

    private function makeExpectedChangeRemainQuantityMessages(OrderDto $orderDto): array
    {
        if (!$orderDto->getDateOrderExecution()) {
            return [];
        }

        $expectedChangeRemainQuantityMessages = [];

        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $expectedChangeRemainQuantityMessages[] = (new ChangeRemainQuantity(
                $orderDto->getStoreId(),
                $cartItemDto->getProductArticle(),
                -$cartItemDto->getQuantity(),
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

    private function initRemainReservesFixture(OrderDto $orderDto): void
    {
        $container = self::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $orderReserveEntity = (new RemainReserve())
                ->setStoreId($orderDto->getStoreId())
                ->setQuantity(self::FIXTURE_REMAIN_RESERVE_QUANTITY)
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

    public function compareChangeRemainQuantityArrays(ChangeRemainQuantity $a, ChangeRemainQuantity $b): int
    {
        $aKey = $a->getReserve() . $a->getStoreId() . $a->getArticle() . $a->getIsDistributor();
        $bKey = $b->getReserve() . $b->getStoreId() . $b->getArticle() . $b->getIsDistributor();

        return $aKey <=> $bKey;
    }
}
