<?php

namespace App\Logger;

use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Обработчик лога валидации для реквестов.
 */
class ValidationProcessor
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function __invoke(array $records): array
    {
        $request = $this->requestStack->getCurrentRequest();

        $canProcess = ($records['context']['exception'] ?? null) instanceof ValidationException && $request;

        if (!$canProcess) {
            return $records;
        }

        // ID основного запроса
        $records['extra']['request_id'] = $request->headers->get('X-Request-Id');

        return $records;
    }
}
