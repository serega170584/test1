<?php

namespace App\Request\Dto\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Результат с ошибкой.
 */
trait ErrorResultTrait
{
    /**
     * Список ошибок.
     *
     * @var ErrorResultItemDto[]
     *
     * @SerializedName("Error")
     */
    private array $error;

    /**
     * @return ErrorResultItemDto[]
     */
    public function getError(): array
    {
        return $this->error;
    }

    /**
     * @param ErrorResultItemDto[] $error
     */
    public function setError(array $error): self
    {
        $this->error = $error;

        return $this;
    }
}
