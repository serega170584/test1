<?php

declare(strict_types=1);

namespace App\Tests\Support\Dto;

class CartItemDto
{
    private string $productArticle;

    private int $quantity;

    private int $reserved;

    private float $price;

    private string $productBarcode;

    private string $productVendorCode;

    private string $productName;

    private ?string $recipeId = null;

    private ?int $recipeConfirm = null;

    private ?array $markingCodes = null;

    public function getProductArticle(): string
    {
        return $this->productArticle;
    }

    public function setProductArticle(string $productArticle): CartItemDto
    {
        $this->productArticle = $productArticle;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): CartItemDto
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getReserved(): int
    {
        return $this->reserved;
    }

    public function setReserved(int $reserved): CartItemDto
    {
        $this->reserved = $reserved;

        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): CartItemDto
    {
        $this->price = $price;

        return $this;
    }

    public function getProductBarcode(): string
    {
        return $this->productBarcode;
    }

    public function setProductBarcode(string $productBarcode): CartItemDto
    {
        $this->productBarcode = $productBarcode;

        return $this;
    }

    public function getProductVendorCode(): string
    {
        return $this->productVendorCode;
    }

    public function setProductVendorCode(string $productVendorCode): CartItemDto
    {
        $this->productVendorCode = $productVendorCode;

        return $this;
    }

    public function getRecipeId(): ?string
    {
        return $this->recipeId;
    }

    public function setRecipeId(?string $recipeId): CartItemDto
    {
        $this->recipeId = $recipeId;

        return $this;
    }

    public function getRecipeConfirm(): ?int
    {
        return $this->recipeConfirm;
    }

    public function setRecipeConfirm(?int $recipeConfirm): CartItemDto
    {
        $this->recipeConfirm = $recipeConfirm;

        return $this;
    }

    public function getMarkingCodes(): ?array
    {
        return $this->markingCodes;
    }

    public function setMarkingCodes(?array $markingCodes): CartItemDto
    {
        $this->markingCodes = $markingCodes;

        return $this;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): CartItemDto
    {
        $this->productName = $productName;

        return $this;
    }
}
