<?php

declare(strict_types=1);

namespace App\Tests\Support\Dto;

class RequestDto
{
    private string $method;

    private string $url;

    private array $headers;

    private string $body;

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return RequestDto
     */
    public function setMethod(string $method): RequestDto
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return RequestDto
     */
    public function setUrl(string $url): RequestDto
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return RequestDto
     */
    public function setHeaders(array $headers): RequestDto
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     * @return RequestDto
     */
    public function setBody(string $body): RequestDto
    {
        $this->body = $body;
        return $this;
    }

}