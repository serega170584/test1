<?php

namespace App\Message;

/**
 * Сообщение удаления резервов заказа.
 */
class OrderReserveRemoving
{
    /**
     * Номер заказа.
     */
    private int $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }
}
