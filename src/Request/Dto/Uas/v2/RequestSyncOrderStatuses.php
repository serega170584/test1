<?php
declare(strict_types=1);

namespace App\Request\Dto\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints;

final class RequestSyncOrderStatuses
{
    /**
     * ID запроса.
     *
     * @SerializedName("Id")
     * @Constraints\NotBlank(message="ID запроса не может быть пустым")
     */
    private string $id;

    /**
     * Номера заказов.
     *
     * @var string[]
     * @SerializedName("Orders")
     * @Constraints\NotBlank(message="Не передан список заказов")
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

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * @param string[] $orders
     *
     * @return $this
     */
    public function setOrders(array $orders): self
    {
        $this->orders = $orders;

        return $this;
    }
}
