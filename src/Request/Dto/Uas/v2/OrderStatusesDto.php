<?php

namespace App\Request\Dto\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Ответ со статусом заказов.
 */
class OrderStatusesDto
{
    use RequestIdTrait;

    /**
     * Статусы заказов.
     *
     * @var OrderStatusDto[]
     *
     * @SerializedName("Orders")
     */
    private array $orders;

    /**
     * Дата и время обработки запроса.
     *
     * @SerializedName("Time")
     */
    private string $time;

    /**
     * @return OrderStatusDto[]
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * @param OrderStatusDto[] $orders
     *
     * @return $this
     */
    public function setOrders(array $orders): self
    {
        $this->orders = $orders;

        return $this;
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function setTime(string $time): self
    {
        $this->time = $time;

        return $this;
    }
}
