<?php

namespace App\Request\Dto\PROVIDER\v1;

use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Чек заказа.
 */
class OrderReceiptDto
{
    public const TYPE_SALE = 1; // Продажа

    /**
     * Номер заказа.
     *
     * @SerializedName("Order")
     */
    private string $order;

    /**
     * Тип чека.
     *
     * @SerializedName("Type")
     */
    private int $type;

    /**
     * Карты лояльности.
     *
     * @var string[]
     * @SerializedName("LoyaltyCard")
     */
    private array $loyaltyCard = [];

    /**
     * Информация о проданных товарах.
     *
     * @var OrderRowDto[]
     * @SerializedName("Rows")
     */
    private array $rows;

    public function getOrder(): string
    {
        return $this->order;
    }

    public function setOrder(string $order): OrderReceiptDto
    {
        $this->order = $order;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): OrderReceiptDto
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getLoyaltyCard(): array
    {
        return $this->loyaltyCard;
    }

    /**
     * @param string[] $loyaltyCard
     */
    public function setLoyaltyCard(array $loyaltyCard): OrderReceiptDto
    {
        $this->loyaltyCard = $loyaltyCard;

        return $this;
    }

    /**
     * @return OrderRowDto[]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @param OrderRowDto[] $rows
     */
    public function setRows(array $rows): OrderReceiptDto
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * @Ignore()
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_SALE,
        ];
    }
}
