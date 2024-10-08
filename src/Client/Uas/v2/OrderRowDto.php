<?php

namespace App\Client\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Позиция в заказе.
 */
class OrderRowDto
{
    /**
     * Кол-во товара.
     *
     * @SerializedName("Quantity")
     */
    private int $quantity;

    /**
     * Кол-во зарезервированого товара.
     *
     * @SerializedName("Reserved")
     */
    private int $reserved;

    /**
     * Сумма заказа.
     *
     * @SerializedName("Sum")
     */
    private float $sum;

    /**
     * Стоимость ед. товара.
     *
     * @SerializedName("Price")
     */
    private float $price;

    /**
     * Идентификатор рецепта при наличии в заказе рецептурных препаратов.
     *
     * @SerializedName("Recipe")
     */
    private ?string $recipe;

    /**
     * Товар.
     *
     * @SerializedName("Product")
     */
    private OrderProductDto $product;

    public function __construct(int $quantity, int $reserved, float $sum, float $price, OrderProductDto $product)
    {
        $this->quantity = $quantity;
        $this->reserved = $reserved;
        $this->sum = $sum;
        $this->price = $price;
        $this->product = $product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getReserved(): int
    {
        return $this->reserved;
    }

    public function getSum(): float
    {
        return $this->sum;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getRecipe(): ?string
    {
        return $this->recipe;
    }

    public function getProduct(): OrderProductDto
    {
        return $this->product;
    }

    public function setRecipe(?string $recipe): self
    {
        $this->recipe = $recipe;

        return $this;
    }
}
