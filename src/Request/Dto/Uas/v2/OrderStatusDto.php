<?php

namespace App\Request\Dto\PROVIDER\v2;

use App\Request\Dto\PROVIDER\OrderStatusInterface;
use App\Request\Dto\PROVIDER\OrderStatusTrait;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Статус заказа.
 */
class OrderStatusDto implements OrderStatusInterface
{
    use OrderStatusTrait;

    /**
     * Номер заказа.
     *
     * @SerializedName("Order")
     */
    private string $order;

    /**
     * Дата последнего обновления заказа.
     *
     * @SerializedName("Date")
     */
    private ?string $date;

    public function getOrder(): string
    {
        return $this->order;
    }

    public function setOrder(string $order): OrderStatusDto
    {
        $this->order = $order;

        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): self
    {
        $this->date = $date;

        return $this;
    }
}
