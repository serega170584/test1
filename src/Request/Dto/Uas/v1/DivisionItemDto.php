<?php

namespace App\Request\Dto\PROVIDER\v1;

/**
 * Информация об остатке.
 */
class DivisionItemDto
{
    /**
     * Артикул.
     */
    private string $code;

    /**
     * Кол-во в остатке.
     */
    private ?int $quantity = null;

    /**
     * Цена.
     */
    private ?float $price = null;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): DivisionItemDto
    {
        $this->code = $code;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): DivisionItemDto
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): DivisionItemDto
    {
        $this->price = $price;

        return $this;
    }
}
