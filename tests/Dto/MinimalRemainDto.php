<?php

namespace App\Tests\Dto;

class MinimalRemainDto
{
    private string $article;

    private int $minimalRemainQuantity;

    /**
     * @return string
     */
    public function getArticle(): string
    {
        return $this->article;
    }

    /**
     * @param string $article
     * @return MinimalRemainDto
     */
    public function setArticle(string $article): MinimalRemainDto
    {
        $this->article = $article;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinimalRemainQuantity(): int
    {
        return $this->minimalRemainQuantity;
    }

    /**
     * @param int $minimalRemainQuantity
     * @return MinimalRemainDto
     */
    public function setMinimalRemainQuantity(int $minimalRemainQuantity): MinimalRemainDto
    {
        $this->minimalRemainQuantity = $minimalRemainQuantity;
        return $this;
    }

}