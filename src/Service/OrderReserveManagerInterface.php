<?php

namespace App\Service;

use test1\Message\V2\ExportOrder;

interface OrderReserveManagerInterface
{
    /**
     * Создание резервов заказа.
     */
    public function createOrderReservesByExportOrder(ExportOrder $order): void;

    /**
     * Удаление резервов заказа.
     *
     * @param int $orderId Номер заказа
     */
    public function removeOrderReservesByOrderId(int $orderId): void;

    /**
     * Удаление устаревших резервов.
     */
    public function removeOutdated(): void;

    /**
     * Поставить задание на отложенное удаление резерва заказа.
     */
    public function dispatchRemoving(int $orderId): void;
}
