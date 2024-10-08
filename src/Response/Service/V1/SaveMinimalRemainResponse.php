<?php

declare(strict_types=1);

namespace App\Response\Service\V1;

class SaveMinimalRemainResponse extends MinimalRemainItem
{
    private string $message;

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): SaveMinimalRemainResponse
    {
        $this->message = $message;

        return $this;
    }
}
