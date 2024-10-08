<?php

namespace App\Request\Dto\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Успешный результат.
 */
trait SuccessResultTrait
{
    /**
     * Текст сообщения.
     *
     * @SerializedName("Message")
     */
    private string $message;

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
