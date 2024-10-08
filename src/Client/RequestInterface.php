<?php

namespace App\Client;

/**
 * Интерфейс API-запроса.
 */
interface RequestInterface
{
    /**
     * Возвращает путь запроса без хоста.
     */
    public function getPath(): string;

    /**
     * Вернет полный адрес запроса.
     */
    public function getUri(): string;

    /**
     * Верент метод запроса.
     */
    public function getMethod(): string;

    /**
     * Вернет список заголовков запроса.
     */
    public function getHeaders(): array;

    /**
     * Вернет тело запроса.
     */
    public function getBody(): ?string;
}
