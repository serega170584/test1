<?php

declare(strict_types=1);

namespace App\Request\Service\V1;

use App\Request\AutowiredRequestInterface;
use Symfony\Component\Validator\Constraints;

class DeleteMinimalRemainRequest implements AutowiredRequestInterface
{
    /**
     * @Constraints\NotBlank()
     * @Constraints\All({
     *     @Constraints\NotBlank(),
     *     @Constraints\Type("string")
     * })
     */
    private array $articles;

    /**
     * @return array<string>
     */
    public function getArticles(): array
    {
        return $this->articles;
    }

    /**
     * @param array<string> $articles
     */
    public function setArticles(array $articles): DeleteMinimalRemainRequest
    {
        $this->articles = $articles;

        return $this;
    }
}
