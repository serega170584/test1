<?php

namespace App\Tests\Command;

use App\Entity\Order;
use App\Entity\OrderReserve;
use App\Entity\RemainReserve;
use App\Manager\FeatureManager;
use App\Tests\Support\Dto\OrderDto;
use App\Tests\Support\Generator\OrderDtoGenerator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Flagception\Manager\FeatureManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ClearReserveCommandTest extends KernelTestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testClearOutdated(OrderDto $orderDto, bool $isEnabledDistributorReserves): void
    {
        $kernel = self::bootKernel();

        $this->initOrderFixture($orderDto);
        $this->initOrderReservesFixture($orderDto);
        $this->initRemainReservesFixture($orderDto);

        $this->initMockFeatureManager($isEnabledDistributorReserves);

        $application = new Application($kernel);
        $command = $application->find('app:reserve:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $orderReserve = $this->findOrderReserveEntity(
                $orderDto->getOrderId(),
                $cartItemDto->getProductArticle()
            );

            if (!$isEnabledDistributorReserves && $orderDto->getDateOrderExecution()) {
                self::assertNotEmpty($orderReserve);
            } else {
                self::assertEmpty($orderReserve);
            }

            $remainReserve = $this->findRemainReserveEntity(
                $orderDto->getStoreId(),
                $cartItemDto->getProductArticle()
            );

            if (!$isEnabledDistributorReserves && $orderDto->getDateOrderExecution()) {
                self::assertNotEmpty($remainReserve->getQuantity());
            } else {
                self::assertEmpty($remainReserve->getQuantity());
            }
        }
    }

    /**
     * @dataProvider dataProvider
     */
    public function testNothingClear(OrderDto $orderDto, bool $isEnabledDistributorReserves): void
    {
        $kernel = self::bootKernel();

        $this->initOrderFixture($orderDto);
        $this->initOrderReservesFixture($orderDto, false);
        $this->initRemainReservesFixture($orderDto);

        $this->initMockFeatureManager($isEnabledDistributorReserves);

        $application = new Application($kernel);
        $command = $application->find('app:reserve:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $orderReserve = $this->findOrderReserveEntity(
                $orderDto->getOrderId(),
                $cartItemDto->getProductArticle()
            );

            self::assertNotEmpty($orderReserve);

            $remainReserve = $this->findRemainReserveEntity(
                $orderDto->getStoreId(),
                $cartItemDto->getProductArticle()
            );

            self::assertNotEmpty($remainReserve->getQuantity());
        }
    }

    /**
     * @dataProvider dataProvider
     */
    public function testsClearByOrderId(OrderDto $orderDto, bool $isEnabledDistributorReserves): void
    {
        $kernel = self::bootKernel();

        $this->initOrderFixture($orderDto);
        $this->initOrderReservesFixture($orderDto, false);
        $this->initRemainReservesFixture($orderDto);

        $this->initMockFeatureManager($isEnabledDistributorReserves);

        $application = new Application($kernel);
        $command = $application->find('app:reserve:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--order-id' => $orderDto->getOrderId(),
        ]);

        $commandTester->assertCommandIsSuccessful();

        foreach ($orderDto->getCartDto()->getItems() as $cartItemDto) {
            $orderReserve = $this->findOrderReserveEntity(
                $orderDto->getOrderId(),
                $cartItemDto->getProductArticle()
            );

            if (!$isEnabledDistributorReserves && $orderDto->getDateOrderExecution()) {
                self::assertNotEmpty($orderReserve);
            } else {
                self::assertEmpty($orderReserve);
            }

            $remainReserve = $this->findRemainReserveEntity(
                $orderDto->getStoreId(),
                $cartItemDto->getProductArticle()
            );

            if (!$isEnabledDistributorReserves && $orderDto->getDateOrderExecution()) {
                self::assertNotEmpty($remainReserve->getQuantity());
            } else {
                self::assertEmpty($remainReserve->getQuantity());
            }
        }
    }

    /**
     * @throws Exception
     */
    public function dataProvider(): iterable
    {
        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->generate();

        yield 'disabled_distributor_reserves' => [$orderDto, false];
        yield 'enabled_distributor_reserves' => [$orderDto, true];

        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->withAddressDelivery()
            ->generate();

        yield 'with_address_delivery--disabled_distributor_reserves' => [$orderDto, false];
        yield 'with_address_delivery--enabled_distributor_reserves' => [$orderDto, true];

        $orderDto = (new OrderDtoGenerator())
            ->withCart()
            ->withDateOrderExecution()
            ->generate();

        yield 'with_date_order_execution--disabled_distributor_reserves' => [$orderDto, false];
        yield 'with_date_order_execution--enabled_distributor_reserves' => [$orderDto, true];
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

    private function initOrderReservesFixture(OrderDto $orderDto, bool $isOutdated = true): void
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

            $createdAt = new DateTime();
            if ($isOutdated) {
                if ($orderDto->getDateOrderExecution()) {
                    $createdAt->modify('-12 day');
                } else {
                    $createdAt->modify('-2 day');
                }
            }
            $orderReserveEntity->setCreatedAt($createdAt);

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
                ->setQuantity($cartItemDto->getQuantity())
                ->setArticle($cartItemDto->getProductArticle());

            $entityManager->persist($orderReserveEntity);
        }
        $entityManager->flush();
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
}
