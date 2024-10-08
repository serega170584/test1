<?php

namespace App\Client\PROVIDER;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Данные для запроса статусов заказов в асинхронном режиме у УАСа.
 */
class OrderStatusesDto
{
    /**
     * ID запроса.
     *
     * @SerializedName("Id")
     */
    private string $id;

    /**
     * Номера заказов.
     *
     * @var string[]
     *
     * @SerializedName("Orders")
     */
    private array $orders;

    public function __construct(string $id, array $orders)
    {
        $this->id = $id;
        $this->orders = $orders;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getOrders(): array
    {
        return $this->orders;
    }
}
