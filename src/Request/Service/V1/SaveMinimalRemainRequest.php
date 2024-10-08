<?php

declare(strict_types=1);

namespace App\Request\Service\V1;

use App\Request\AutowiredRequestInterface;
use Symfony\Component\Validator\Constraints;

class SaveMinimalRemainRequest implements AutowiredRequestInterface
{
    /**
     * @Constraints\NotBlank
     */
    private string $article;

    /**
     * @Constraints\NotNull
     * @Constraints\Positive
     */
    private int $minimalRemainQuantity;

    public function getArticle(): string
    {
        return $this->article;
    }

    public function setArticle(string $article): SaveMinimalRemainRequest
    {
        $this->article = $article;

        return $this;
    }

    public function getMinimalRemainQuantity(): int
    {
        return $this->minimalRemainQuantity;
    }

    public function setMinimalRemainQuantity(int $minimalRemainQuantity): SaveMinimalRemainRequest
    {
        $this->minimalRemainQuantity = $minimalRemainQuantity;

        return $this;
    }
}
