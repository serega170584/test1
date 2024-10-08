<?php

namespace App\Request\Dto\PROVIDER;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class EchoRequestDto
{
    #[Type('string')]
    #[NotBlank()]
    public string $message;

    public function getMessage(): string
    {
        return $this->message;
    }
}
