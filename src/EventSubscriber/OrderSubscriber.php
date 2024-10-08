<?php

namespace App\EventSubscriber;

use App\Event\OrderExportEvent;
use App\Event\OrderImportEvent;
use App\Repository\OrderRepository;
use App\Request\Dto\OrderStatusInterface as ImOrderStatus;
use App\Request\Dto\OrderTypeInterface;
use App\Request\Dto\PROVIDER\OrderStatusInterface as PROVIDERStatus;
use App\Service\OrderReserveManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Обработка событий заказов.
 */
class OrderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private OrderReserveManagerInterface $reserveManager,
        private OrderRepository $orderRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderExportEvent::class => 'onOrderExport',
            OrderImportEvent::class => 'onOrderImport',
        ];
    }

    /**
     * Обработка экспорта данных заказа.
     */
    public function onOrderExport(OrderExportEvent $event): void
    {
        $order = $event->getOrder();

        // Заказ не из аптеки
        if ($order->getType() !== OrderTypeInterface::TYPE_PROVIDER) {
            return;
        }

        switch ($order->getStatus()) {
            // Создание
            case ImOrderStatus::STATUS_CREATED:
                $this->reserveManager->createOrderReservesByExportOrder($order);
                break;

            // Отмена
            case ImOrderStatus::STATUS_NOT_REDEEMED:
                if (!$this->orderRepository->find($order->getNumber())?->isDistributor()) {
                    break;
                }
                // no break
            case ImOrderStatus::STATUS_CANCELLED:
                $this->reserveManager->removeOrderReservesByOrderId((int) $order->getNumber());
                break;
        }
    }

    /**
     * Обработка импорта данных заказа.
     */
    public function onOrderImport(OrderImportEvent $event): void
    {
        if ($event->getType() !== OrderTypeInterface::TYPE_PROVIDER) {
            return;
        }

        $triggerStatuses = [
            PROVIDERStatus::STATUS_READY,
            PROVIDERStatus::STATUS_PART_READY,
        ];

        $orderEntity = $this->orderRepository->find($event->getNumber());
        if ($orderEntity?->isDistributor()) {
            $triggerStatuses = [
                PROVIDERStatus::STATUS_CANCELED,
            ];
        }

        if (!in_array($event->getPROVIDERStatus(), $triggerStatuses, true)) {
            return;
        }

        // Отложенное удаление резервов
        $this->reserveManager->dispatchRemoving((int) $event->getNumber());
    }
}
