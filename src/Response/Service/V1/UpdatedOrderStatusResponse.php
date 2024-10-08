<?php

namespace App\Response\Service\V1;

class UpdatedOrderStatusResponse
{
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
