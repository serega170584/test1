<?php
declare(strict_types=1);

namespace App\Client\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\SerializedName;

final class SendSyncOrdersStatuses
{
    public function __construct(
        /**
         * @SerializedName("ID")
         *
         * @var OrderStatusItem[]
         */
        private readonly string $id,

        /**
         * @SerializedName("Orders")
         *
         * @var OrderStatusItem[]
         */
        private array $orders = []
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function addOrderItem(OrderStatusItem $item): self
    {
        $this->orders[] = $item;

        return $this;
    }

    /**
     * @return OrderStatusItem[]
     */
    public function getOrders(): array
    {
        return $this->orders;
    }
}
