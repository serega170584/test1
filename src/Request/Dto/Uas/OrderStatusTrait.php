<?php

namespace App\Request\Dto\PROVIDER;

use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Расширения статусов заказа.
 */
trait OrderStatusTrait
{
    /**
     * Статус заказа.
     *
     * @SerializedName("Status")
     */
    private ?int $status;

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): OrderStatusInterface
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @Ignore
     */
    public function isStatusPartReady(): bool
    {
        return $this->getStatus() === OrderStatusInterface::STATUS_PART_READY;
    }

    /**
     * //TODO вынести из DTO.
     *
     * @Ignore()
     */
    public static function getStatuses(): array
    {
        return [
            OrderStatusInterface::STATUS_CREATED,
            OrderStatusInterface::STATUS_CANCELED,
            OrderStatusInterface::STATUS_READY,
            OrderStatusInterface::STATUS_PART_READY,
            OrderStatusInterface::STATUS_ASSEMBLING,
            OrderStatusInterface::STATUS_COMPLETED,
            OrderStatusInterface::STATUS_WAIT_MDLP,
            OrderStatusInterface::STATUS_NULLIFIED,
            // TODO закрыть фичетоглом, чтобы не было 500 при выключенном фичетогле IS_ENABLED_provCY_DELIVERY_TO_CUSTOMER
            OrderStatusInterface::STATUS_TRANSFERRED_TO_COURIER,
            OrderStatusInterface::STATUS_NON_PURCHASE_ACCEPTED,
            OrderStatusInterface::STATUS_NON_PURCHASE_PARTIALLY_ACCEPTED,
            OrderStatusInterface::STATUS_RECIPE_COMPLETED,
            OrderStatusInterface::STATUS_ON_TRADES,
        ];
    }
}
