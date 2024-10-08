<?php

declare(strict_types=1);

namespace App\Dto;

class SaveMinimalRemainDto
{
    /**
     * Артикул товара.
     */
    private string $article;

    /**
     * Минимальный остаток для товара.
     */
    private int $minimalRemainQuantity;

    public function getArticle(): string
    {
        return $this->article;
    }

    public function setArticle(string $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function getMinimalRemainQuantity(): int
    {
        return $this->minimalRemainQuantity;
    }

    public function setMinimalRemainQuantity(int $minimalRemainQuantity): self
    {
        $this->minimalRemainQuantity = $minimalRemainQuantity;

        return $this;
    }
}
