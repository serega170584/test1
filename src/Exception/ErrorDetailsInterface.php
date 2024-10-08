<?php

namespace App\Exception;

/**
 * Интерфейс детализации ошибок.
 */
interface ErrorDetailsInterface
{
    /**
     * Возвращает список ошибок.
     */
    public function getErrorDetails(): array;
}
