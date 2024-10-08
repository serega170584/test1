<?php

declare(strict_types=1);

namespace App\Tests\Support\Generator;

use App\Tests\Support\Dto\CartDto;
use App\Tests\Support\Dto\CartItemDto;
use Exception;

class CartDtoGenerator
{
    private bool $withRecipe = false;

    private bool $withEmptyReserved = false;

    private bool $withRecipeConfirm = false;

    private bool $withMarkingCodes = false;

    /**
     * @throws Exception
     */
    public function generate(): CartDto
    {
        $cartDto = new CartDto();

        $itemsCount = random_int(2, 5);

        for ($i = 1; $i <= $itemsCount; $i++) {
            $quantity = random_int(1, 10);
            $cartItemDto = (new CartItemDto())
                ->setProductArticle((string) random_int(1000000, 9999999))
                ->setQuantity($quantity)
                ->setReserved($this->withEmptyReserved ? 0 : $quantity)
                ->setPrice(random_int(10000, 1000000) / 100)
                ->setProductBarcode((string) random_int(1000000000, 9999999999))
                ->setProductVendorCode((string) random_int(1000000000, 9999999999))
                ->setProductName("Товар #{$i}");

            if ($this->withRecipe) {
                $cartItemDto->setRecipeId('recipe-id');
            }
            if ($this->withRecipeConfirm) {
                $cartItemDto->setRecipeConfirm($quantity);
            }
            if ($this->withMarkingCodes) {
                $markingCodes = [];
                for ($j = 1; $j <= $quantity; $j++) {
                    $markingCodes[] = bin2hex(random_bytes(10));
                }
                $cartItemDto->setMarkingCodes($markingCodes);
            }

            $cartDto->addItem($cartItemDto);
        }

        return $cartDto;
    }

    public function withRecipe(): CartDtoGenerator
    {
        $this->withRecipe = true;

        return $this;
    }

    public function withEmptyReserved(): CartDtoGenerator
    {
        $this->withEmptyReserved = true;

        return $this;
    }

    public function withRecipeConfirm(): CartDtoGenerator
    {
        $this->withRecipeConfirm = true;

        return $this;
    }

    public function withMarkingCodes(): CartDtoGenerator
    {
        $this->withMarkingCodes = true;

        return $this;
    }
}
