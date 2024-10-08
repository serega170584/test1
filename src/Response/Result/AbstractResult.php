<?php

namespace App\Response\Result;

use OpenApi\Annotations as OA;

/**
 * Абстрактный результат ответа.
 */
abstract class AbstractResult
{
    /**
     * Текст сообщения.
     *
     * @OA\Property(description="Текст сообщения.")
     */
    private string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
