<?php

declare(strict_types=1);

namespace App\Tests\Support\Dto;

class CartDto
{
    private array $items;

    /**
     * @return array<CartItemDto>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param CartItemDto $cartItemDto
     * @return $this
     */
    public function addItem(CartItemDto $cartItemDto): CartDto
    {
        $this->items[] = $cartItemDto;
        return $this;
    }
}