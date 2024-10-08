<?php

namespace App\Request\Dto\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Результат запроса по заказу.
 */
class OrderResultDto
{
    /**
     * ID запроса.
     *
     * @SerializedName("Id")
     * @Groups({"success", "error"})
     */
    private string $id;

    /**
     * Номер заказа.
     *
     * @SerializedName("OrderId")
     * @Groups({"success", "error"})
     */
    private string $orderId;

    /**
     * Текст сообщения.
     *
     * @SerializedName("Message")
     * @Groups({"success"})
     */
    private ?string $message = null;

    /**
     * Ошибка.
     *
     * @SerializedName("Error")
     * @Groups({"error"})
     */
    private ?ErrorResultItemDto $error = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getError(): ?ErrorResultItemDto
    {
        return $this->error;
    }

    /**
     * @return OrderResultDto
     */
    public function setError(?ErrorResultItemDto $error): self
    {
        $this->error = $error;

        return $this;
    }
}
