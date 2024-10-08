<?php

namespace App\Request\Dto\PROVIDER\v1;

use App\Request\Dto\PROVIDER\OrderStatusInterface;
use App\Request\Dto\PROVIDER\OrderStatusTrait;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Данные о заказе.
 */
class OrderDto implements OrderStatusInterface
{
    use OrderStatusTrait;

    public const TYPE_WAREHOUSE = 1; // Со склада
    public const TYPE_BOOKING_provCY = 2; // бронирование в аптеке

    /**
     * Тип заказа.
     *
     * @SerializedName("TypeOrder")
     */
    private int $typeOrder;

    /**
     * Номер заказа.
     *
     * @SerializedName("Number")
     */
    private string $number;

    /**
     * Код КИС XXX-XXXXXX или XX-XXXXXXX.
     *
     * @SerializedName("UserEdit")
     */
    private ?string $userEdit = null;

    /**
     * Дата изменения в формате дд.мм.гггг чч:мм:сс
     *
     * @SerializedName("TimeEdit")
     */
    private ?string $timeEdit = null;

    /**
     * Информация о товарах в заказе.
     *
     * @var OrderRowDto[]
     * @SerializedName("Rows")
     */
    private array $rows = [];

    public function getTypeOrder(): int
    {
        return $this->typeOrder;
    }

    public function setTypeOrder(int $typeOrder): OrderDto
    {
        $this->typeOrder = $typeOrder;

        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): OrderDto
    {
        $this->number = $number;

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
    public function setRows(array $rows): OrderDto
    {
        $this->rows = $rows;

        return $this;
    }

    public function getUserEdit(): ?string
    {
        return $this->userEdit;
    }

    public function setUserEdit(?string $userEdit): OrderDto
    {
        $this->userEdit = $userEdit;

        return $this;
    }

    public function getTimeEdit(): ?string
    {
        return $this->timeEdit;
    }

    public function setTimeEdit(?string $timeEdit): OrderDto
    {
        $this->timeEdit = $timeEdit;

        return $this;
    }

    /**
     * @Ignore()
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_WAREHOUSE,
            self::TYPE_BOOKING_provCY,
        ];
    }
}
