<?php

declare(strict_types=1);

namespace App\Response\Service\V1;

class ImportPROVIDERGoodsResponse
{
    private string $message;

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): ImportPROVIDERGoodsResponse
    {
        $this->message = $message;

        return $this;
    }
}
