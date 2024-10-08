<?php

namespace App\Client;

/**
 * Базовый класс запроса для api-клиентов.
 */
class ApiRequest implements RequestInterface
{
    /**
     * Базовый адерс url-запроса.
     */
    private string $baseUri = '';

    /**
     * Url-адрес относительно baseUri.
     */
    private string $path;

    /**
     * HTTP-метод запроса.
     */
    private string $method;

    /**
     * Заголовки запроса.
     */
    private array $headers;

    /**
     * Тело запроса.
     */
    private ?string $body;

    public function __construct(string $method, string $path, array $headers = [], $body = null)
    {
        $this->path = $path;
        $this->method = $method;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getUri(): string
    {
        return $this->baseUri . $this->path;
    }

    public function setBaseUri(string $baseUri): self
    {
        $this->baseUri = $baseUri;

        return $this;
    }
}
