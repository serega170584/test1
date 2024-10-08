<?php

namespace App\Request\Dto\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Описание ошибки.
 */
class ErrorResultItemDto
{
    /**
     * Текст с ошибкой.
     *
     * @SerializedName("Message")
     */
    private string $message;

    /**
     * Код ошибки.
     *
     * @SerializedName("Code")
     */
    private string $code;

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }
}
