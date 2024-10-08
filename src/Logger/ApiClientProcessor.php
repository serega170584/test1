<?php

namespace App\Logger;

use App\Exception\ApiClientExceptionInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Обработка логов API-клиентов.
 */
class ApiClientProcessor
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function __invoke(array $records): array
    {
        $exception = $records['context']['exception'] ?? null;

        if (!$exception instanceof ApiClientExceptionInterface) {
            return $records;
        }

        $clientRequest = $exception->getRequest();
        $clientResponse = $exception->getResponse();

        $records['extra']['api_client'] = [
            'request_uri' => sprintf('%s %s', $clientRequest->getMethod(), $clientRequest->getUri()),
            'request_body' => $clientRequest->getBody(),
            'request_headers' => json_encode($clientRequest->getHeaders()),
            'http_code' => $exception->getStatusCode(),
            'response_headers' => json_encode($clientResponse->getHeaders(false)),
            'response_body' => $clientResponse->getContent(false),
        ];

        // ID основного запроса
        if ($request = $this->requestStack->getCurrentRequest()) {
            $records['extra']['request_id'] = $request->headers->get('X-Request-Id');
        }

        return $records;
    }
}
