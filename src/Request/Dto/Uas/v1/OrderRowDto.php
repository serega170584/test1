<?php

namespace App\Request\Dto\PROVIDER\v1;

use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Данные о позиции в заказе.
 */
class OrderRowDto
{
    /**
     * Товар
     *
     * @SerializedName("Product")
     */
    private OrderProductDto $product;

    /**
     * Кол-во товара, которое запросил клиент
     *
     * @SerializedName("Quantity")
     */
    private int $quantity;

    /**
     * Количество товара, которое доступно в аптеке.
     *
     * @SerializedName("Reserved")
     */
    private ?int $reserved = null;

    /**
     * Сумма товара, рассчитанная как количество*цена.
     *
     * @SerializedName("Sum")
     */
    private float $sum;

    /**
     * Количество товара, которое разрешено к отпуску исходя из данных в рецепте по товару из заказа.
     *
     * @SerializedName("RecipeConfirm")
     */
    private ?int $recipeConfirm = null;

    /**
     * Массив исходных кодов маркированного товара.
     *
     * @var string[]|null
     * @SerializedName("MarkingCodes")
     */
    private ?array $markingCodes = null;

    public function getProduct(): OrderProductDto
    {
        return $this->product;
    }

    public function setProduct(OrderProductDto $product): OrderRowDto
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): OrderRowDto
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getReserved(): ?int
    {
        return $this->reserved;
    }

    public function setReserved(?int $reserved): OrderRowDto
    {
        $this->reserved = $reserved;

        return $this;
    }

    /**
     * @Ignore
     */
    public function getPrice(): float
    {
        return $this->getSum() && $this->getQuantity()
            ? round($this->getSum() / $this->getQuantity(), 5)
            : 0.0;
    }

    public function getSum(): float
    {
        return $this->sum;
    }

    public function setSum(float $sum): OrderRowDto
    {
        $this->sum = $sum;

        return $this;
    }

    public function setRecipeConfirm(?int $recipeConfirm): self
    {
        $this->recipeConfirm = $recipeConfirm;

        return $this;
    }

    public function getRecipeConfirm(): ?int
    {
        return $this->recipeConfirm;
    }

    public function setMarkingCodes(?array $markingCodes): OrderRowDto
    {
        $this->markingCodes = $markingCodes;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getMarkingCodes(): ?array
    {
        return $this->markingCodes;
    }
}
