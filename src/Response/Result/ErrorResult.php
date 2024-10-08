<?php

namespace App\Response\Result;

/**
 * Результат ответа с ошибкой.
 */
class ErrorResult extends AbstractResult
{
    /**
     * Список сообщений об ошибках.
     *
     * @var string[]
     */
    private array $errors;

    public function __construct(string $message, array $errors)
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
