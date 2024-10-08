<?php

declare(strict_types=1);

namespace App\Response\Service\V1;

class ProcessFailedMessagesResponse
{
    private string $message;

    private int $countProcessed;

    private int $countRemaining;

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): ProcessFailedMessagesResponse
    {
        $this->message = $message;

        return $this;
    }

    public function getCountProcessed(): int
    {
        return $this->countProcessed;
    }

    public function setCountProcessed(int $countProcessed): ProcessFailedMessagesResponse
    {
        $this->countProcessed = $countProcessed;

        return $this;
    }

    public function getCountRemaining(): int
    {
        return $this->countRemaining;
    }

    public function setCountRemaining(int $countRemaining): ProcessFailedMessagesResponse
    {
        $this->countRemaining = $countRemaining;

        return $this;
    }
}
