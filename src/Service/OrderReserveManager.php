<?php

namespace App\Service;

use App\Entity\OrderReserve;
use App\Manager\FeatureManager;
use App\Message\OrderReserveRemoving;
use App\Repository\OrderRepository;
use App\Repository\OrderReserveRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use test1\Message\V2\ChangeRemainQuantity;
use test1\Message\V2\ExportOrder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Throwable;

/**
 * Менеджер резервов.
 */
class OrderReserveManager implements OrderReserveManagerInterface
{
    private EntityManagerInterface $em;

    private LoggerInterface $logger;

    private MessageBusInterface $messageBus;

    private RemainReserveManager $remainReserveManager;

    /**
     * Задержка перед удалением резервов.
     */
    private int $removingDelay;

    private OrderReserveRepository $orderReserveRepository;

    private OrderRepository $orderRepository;

    private int $passedDaysForOutdatedNotDistributorReserves;

    private int $passedDaysForOutdatedDistributorReserves;

    private FeatureManager $featureManager;

    public function __construct(
        int $removingDelay,
        int $passedDaysForOutdatedNotDistributorReserves,
        int $passedDaysForOutdatedDistributorReserves,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        MessageBusInterface $messageBus,
        RemainReserveManager $remainReserveManager,
        OrderReserveRepository $orderReserveRepository,
        OrderRepository $orderRepository,
        FeatureManager $featureManager
    ) {
        $this->em = $em;
        $this->logger = $logger;
        $this->messageBus = $messageBus;
        $this->remainReserveManager = $remainReserveManager;
        $this->removingDelay = $removingDelay;
        $this->orderReserveRepository = $orderReserveRepository;
        $this->orderRepository = $orderRepository;
        $this->passedDaysForOutdatedNotDistributorReserves = $passedDaysForOutdatedNotDistributorReserves;
        $this->passedDaysForOutdatedDistributorReserves = $passedDaysForOutdatedDistributorReserves;
        $this->featureManager = $featureManager;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatchRemoving(int $orderId): void
    {
        $orderEntity = $this->orderRepository->find($orderId);
        if ($orderEntity?->isDistributor() && !$this->featureManager->isEnabledDistributorsReserves()) {
            return;
        }

        $this->messageBus->dispatch(new OrderReserveRemoving($orderId), [
            new DelayStamp($this->removingDelay),
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     * @throws Throwable
     */
    public function createOrderReservesByExportOrder(ExportOrder $order): void
    {
        $orderId = (int) $order->getNumber();

        $orderEntity = $this->orderRepository->find($orderId);
        if ($orderEntity?->isDistributor() && !$this->featureManager->isEnabledDistributorsReserves()) {
            return;
        }

        $this->logger->info(sprintf('reserves.handle: creating reserves for order #%d', $orderId));

        // Ищем 1 запись с резервом заказа
        $orderReserve = $this->orderReserveRepository->findOneBy([
            'orderId' => $orderId,
        ]);

        // Резервы по заказу найдены
        if ($orderReserve) {
            return;
        }

        // Группировка по артикулу, из-за того, что иногда из монолита приходят заказы, в которых один и тот же товар разными позициями в корзине
        $orderReservesByArticle = [];
        foreach ($order->getItems() as $item) {
            $article = $item->getCode();

            /** @var OrderReserve $reserve */
            if ($reserve = $orderReservesByArticle[$article] ?? null) {
                $reserve->setQuantity($reserve->getQuantity() + $item->getQuantity());
            } else {
                $reserve =
                    (new OrderReserve())
                        ->setArticle($item->getCode())
                        ->setOrderId($orderId)
                        ->setStoreId($order->getDivision())
                        ->setQuantity($item->getQuantity());
            }

            $orderReservesByArticle[$article] = $reserve;
        }

        foreach ($orderReservesByArticle as $reserve) {
            $this->saveOrderReserve($reserve);
        }

        $this->logger->info(sprintf('reserves.handle: reserves successfully created for order #%d', $orderId));
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     * @throws Throwable
     */
    public function removeOrderReservesByOrderId(int $orderId): void
    {
        $orderEntity = $this->orderRepository->find($orderId);
        if ($orderEntity?->isDistributor() && !$this->featureManager->isEnabledDistributorsReserves()) {
            return;
        }

        $this->logger->info(sprintf('reserves.handle: removing reserves for order #%d', $orderId));

        /** @var OrderReserve[] $reserves */
        $reserves = $this->orderReserveRepository->findBy([
            'orderId' => $orderId,
        ]);

        // нечего удалять
        if (!$reserves) {
            return;
        }

        foreach ($reserves as $reserve) {
            $this->deleteOrderReserve($reserve);
        }

        $this->logger->info(sprintf('reserves.handle: reserves successfully removed for order #%d', $orderId));
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     * @throws Throwable
     */
    public function removeOutdated(): void
    {
        $orderReserves = $this->orderReserveRepository->findOutdatedWithoutDistributorsByPassedDays($this->passedDaysForOutdatedNotDistributorReserves);

        foreach ($orderReserves as $orderReserve) {
            $this->deleteOrderReserve($orderReserve);
        }

        if ($this->featureManager->isEnabledDistributorsReserves()) {
            $orderDistributorReserves = $this->orderReserveRepository->findOutdatedOnlyDistributorsByPassedDays($this->passedDaysForOutdatedDistributorReserves);

            foreach ($orderDistributorReserves as $orderDistributorReserve) {
                $this->deleteOrderReserve($orderDistributorReserve);
            }
        }
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    private function saveOrderReserve(OrderReserve $orderReserve): void
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->remainReserveManager->recalculate($orderReserve);
            $this->orderReserveRepository->save($orderReserve);

            $this->em->getConnection()->commit();
        } catch (Throwable $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }

        $order = $this->orderRepository->find($orderReserve->getOrderId());

        $changeRemainQuantity = (new ChangeRemainQuantity(
            $orderReserve->getStoreId(),
            $orderReserve->getArticle(),
            $orderReserve->getQuantity()
        ))
            ->setIsDistributor((bool) $order?->isDistributor());

        $this->dispatchChangeRemainQuantity($changeRemainQuantity);
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    private function deleteOrderReserve(OrderReserve $orderReserve): void
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->remainReserveManager->recalculate($orderReserve, -1);
            $this->orderReserveRepository->delete($orderReserve);

            $this->em->getConnection()->commit();
        } catch (Throwable $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }

        $order = $this->orderRepository->find($orderReserve->getOrderId());

        if (!$order || !$order->isDistributor()) {
            return;
        }

        $changeRemainQuantity = (new ChangeRemainQuantity(
            $orderReserve->getStoreId(),
            $orderReserve->getArticle(),
            -$orderReserve->getQuantity()
        ))
            ->setIsDistributor($order->isDistributor());

        $this->dispatchChangeRemainQuantity($changeRemainQuantity);
    }

    private function dispatchChangeRemainQuantity(ChangeRemainQuantity $changeRemainQuantity): void
    {
        $this->messageBus->dispatch($changeRemainQuantity, [
            new AmqpStamp('change-remain-quantity'), // TODO выпилить после переезда на кафку
        ]);
    }
}
