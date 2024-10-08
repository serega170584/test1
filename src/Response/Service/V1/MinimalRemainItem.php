<?php

declare(strict_types=1);

namespace App\Response\Service\V1;

class MinimalRemainItem
{
    private string $article;

    private int $minimalRemainQuantity;

    private string $createdAt;

    private string $updatedAt;

    public function getArticle(): string
    {
        return $this->article;
    }

    public function setArticle(string $article): MinimalRemainItem
    {
        $this->article = $article;

        return $this;
    }

    public function getMinimalRemainQuantity(): int
    {
        return $this->minimalRemainQuantity;
    }

    public function setMinimalRemainQuantity(int $minimalRemainQuantity): MinimalRemainItem
    {
        $this->minimalRemainQuantity = $minimalRemainQuantity;

        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): MinimalRemainItem
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(string $updatedAt): MinimalRemainItem
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
