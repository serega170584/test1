<?php

declare(strict_types=1);

namespace App\Response\Service\V1;

class DeleteMinimalRemainResponse
{
    private string $message;

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): DeleteMinimalRemainResponse
    {
        $this->message = $message;

        return $this;
    }
}
