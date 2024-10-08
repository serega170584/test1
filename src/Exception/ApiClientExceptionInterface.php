<?php

namespace App\Exception;

use App\Client\RequestInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

interface ApiClientExceptionInterface
{
    /**
     * Вернет запрос http-клиента.
     */
    public function getRequest(): RequestInterface;

    /**
     * Верент ответ, полученный http-клиентом.
     */
    public function getResponse(): ResponseInterface;

    /**
     * Вернет код ответа.
     */
    public function getStatusCode(): int;
}
