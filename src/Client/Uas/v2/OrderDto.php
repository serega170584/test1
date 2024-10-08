<?php

namespace App\Client\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Данные о новом заказе.
 */
class OrderDto
{
    /**
     * ID запроса.
     *
     * @SerializedName("Id")
     * @Groups({"update"})
     */
    private string $id;

    /**
     * Номер заказа.
     *
     * @SerializedName("Number")
     * @Groups({"update"})
     */
    private string $number;

    /**
     * Статус.
     *
     * @SerializedName("Status")
     * @Groups({"update"})
     */
    private int $status;

    /**
     * Тип заказа.
     *
     * @SerializedName("TypeOrder")
     * @Groups({"update"})
     */
    private int $typeOrder;

    /**
     * Время создания заказа.
     *
     * @SerializedName("DateOrder")
     */
    private string $dateOrder;

    /**
     * Код аптеки.
     *
     * @SerializedName("Division")
     */
    private string $division;

    /**
     * Телефон клиента.
     *
     * @SerializedName("Phone")
     */
    private string $phone;

    /**
     * Планируемая дата доставки.
     *
     * @SerializedName("DateProvidingOrder")
     */
    private string $dateProvidingOrder;

    /**
     * Плановая дата поставки товара от дистрибьютора в аптеку.
     *
     * @SerializedName("DateOrderExecution")
     */
    private ?string $dateOrderExecution = null;

    /**
     * Адрес доставки заказа клиенту.
     *
     * @SerializedName("AddressDelivery")
     */
    private ?string $addressDelivery = null;

    /**
     * Код подтверждения для выдачи/возврата заказа.
     *
     * @
     * @SerializedName("AcceptCode")
     * @Groups({"update"})
     */
    private ?string $acceptCode = null;

    /**
     * Тип оплаты.
     *
     * @SerializedName("TypePay")
     */
    private int $typePay;

    /**
     * Привязка к аптеке-складу.
     *
     * @SerializedName("DivisionPost")
     */
    private string $divisionPost;

    /**
     * Сумма онлайн оплаты.
     *
     * @SerializedName("SumPay")
     */
    private float $sumPay;

    /**
     * Комментарий к заказу.
     *
     * @SerializedName("Comment")
     */
    private string $comment;

    /**
     * Название партнера.
     *
     * @SerializedName("Partner")
     */
    private string $partner;

    /**
     * Позиции заказа.
     *
     * @var OrderRowDto[]
     * @SerializedName("Rows")
     */
    private array $rows;

    public function __construct(string $id, string $number, int $status, int $typeOrder, array $rows = [])
    {
        $this->id = $id;
        $this->number = $number;
        $this->status = $status;
        $this->typeOrder = $typeOrder;
        $this->rows = $rows;
    }

    /**
     * @return $this
     */
    public function setDateOrder(string $dateOrder): self
    {
        $this->dateOrder = $dateOrder;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDivision(string $division): self
    {
        $this->division = $division;

        return $this;
    }

    /**
     * @return $this
     */
    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDateProvidingOrder(string $dateProvidingOrder): self
    {
        $this->dateProvidingOrder = $dateProvidingOrder;

        return $this;
    }

    /**
     * @return $this
     */
    public function setTypePay(int $typePay): self
    {
        $this->typePay = $typePay;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDivisionPost(string $divisionPost): self
    {
        $this->divisionPost = $divisionPost;

        return $this;
    }

    /**
     * @return $this
     */
    public function setSumPay(float $sumPay): self
    {
        $this->sumPay = $sumPay;

        return $this;
    }

    /**
     * @return $this
     */
    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return $this
     */
    public function setPartner(string $partner): self
    {
        $this->partner = $partner;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getTypeOrder(): int
    {
        return $this->typeOrder;
    }

    public function getDateOrder(): string
    {
        return $this->dateOrder;
    }

    public function getDivision(): string
    {
        return $this->division;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getDateProvidingOrder(): string
    {
        return $this->dateProvidingOrder;
    }

    /**
     * @return string
     */
    public function getDateOrderExecution(): ?string
    {
        return $this->dateOrderExecution;
    }

    public function getAddressDelivery(): ?string
    {
        return $this->addressDelivery;
    }

    public function getAcceptCode(): ?string
    {
        return $this->acceptCode;
    }

    public function getTypePay(): int
    {
        return $this->typePay;
    }

    public function getDivisionPost(): string
    {
        return $this->divisionPost;
    }

    public function getSumPay(): float
    {
        return $this->sumPay;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getPartner(): string
    {
        return $this->partner;
    }

    /**
     * @return OrderRowDto[]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function setAddressDelivery(?string $addressDelivery): self
    {
        $this->addressDelivery = $addressDelivery;

        return $this;
    }

    public function setAcceptCode(?string $acceptCode): self
    {
        $this->acceptCode = $acceptCode;

        return $this;
    }

    /**
     * @param string $dateOrderExecution
     */
    public function setDateOrderExecution(?string $dateOrderExecution): self
    {
        $this->dateOrderExecution = $dateOrderExecution;

        return $this;
    }
}
