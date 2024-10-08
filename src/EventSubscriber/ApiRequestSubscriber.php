<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

/**
 * Обработка запросов сервисов.
 */
class ApiRequestSubscriber implements EventSubscriberInterface
{
    private const HEADER_REQUEST_ID = 'X-Request-Id';

    /**
     * Разрешение логировать.
     */
    private bool $logEnabled;

    private LoggerInterface $logger;

    public function __construct(bool $logEnabled, LoggerInterface $apiRequestLogger)
    {
        $this->logger = $apiRequestLogger;
        $this->logEnabled = $logEnabled;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$this->canHandle($event)) {
            return;
        }

        $request = $event->getRequest();

        // Определяем request_id - он же будет trace_id для exception
        if (!$requestId = $request->headers->get(self::HEADER_REQUEST_ID)) {
            $requestId = Uuid::v4()->toRfc4122();
            $request->headers->set(self::HEADER_REQUEST_ID, $requestId);
        }

        $requestUri = sprintf('%s %s', $request->getMethod(), $request->getRequestUri());
        $tag = sprintf('request.%s', $request->attributes->get('_route'));

        $message = sprintf('Request %s', $requestUri);

        $this->logger->info($message, [
            'tag' => $tag,
            'request_id' => $requestId,
            'request_uri' => $requestUri,
            'headers' => json_encode($request->headers->all()),
            'body' => $request->getContent(),
        ]);
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$this->canHandle($event)) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        $requestId = $request->headers->get(self::HEADER_REQUEST_ID);
        $requestUri = sprintf('%s %s', $request->getMethod(), $request->getRequestUri());
        $tag = sprintf('response.%s', $request->attributes->get('_route'));

        $message = sprintf('%s Response %s', $response->getStatusCode(), $requestUri);

        $this->logger->info($message, [
            'tag' => $tag,
            'request_id' => $requestId,
            'request_uri' => $requestUri,
            'http_code' => $response->getStatusCode(),
            'headers' => json_encode($response->headers->all()),
            'body' => $response->getContent(),
        ]);
    }

    /**
     * Проверка возможности логирования.
     */
    private function canHandle(KernelEvent $event): bool
    {
        $route = $event->getRequest()->attributes->get('_route');

        return $event->isMainRequest()
            && $this->logEnabled
            && $route
            && $this->canLogRoute($route);
    }

    /**
     * Вернет истину, если можно логировать запрошенный роут
     */
    private function canLogRoute(string $route): bool
    {
        // Исключаем роутинг не относящийся к API
        return str_starts_with($route, 'api.');
    }
}
