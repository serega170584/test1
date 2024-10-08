<?php

namespace App\Request\Dto\PROVIDER\v1;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Данные о товаре в заказе.
 */
class OrderProductDto
{
    /**
     * Артикул товара.
     *
     * @SerializedName("Code")
     */
    private string $code;

    /**
     * Наименование товара.
     *
     * @SerializedName("Name")
     */
    private ?string $name;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): OrderProductDto
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): OrderProductDto
    {
        $this->name = $name;

        return $this;
    }
}
