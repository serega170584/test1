<?php

namespace App\Exception;

use App\Client\RequestInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * HTTP ошибка запроса апи-клиента.
 */
final class ApiClientException extends \RuntimeException implements ApiClientExceptionInterface
{
    /**
     * Запрос клиента.
     */
    private RequestInterface $request;

    /**
     * Ответ полученный клиентом.
     */
    private ResponseInterface $response;

    public function __construct(
        string $message,
        RequestInterface $request,
        ResponseInterface $response,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->response = $response;
        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * {@inheritDoc}
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusCode(): int
    {
        try {
            return $this->getResponse()->getStatusCode();
        } catch (\Throwable $exception) {
            return Response::HTTP_INTERNAL_SERVER_ERROR;
        }
    }
}
