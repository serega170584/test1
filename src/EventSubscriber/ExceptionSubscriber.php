<?php

namespace App\EventSubscriber;

use App\Exception\ApiClientExceptionInterface;
use App\Exception\ErrorDetailsInterface;
use App\Exception\TranslatableInterface;
use App\Response\ApiResponse;
use App\Response\Result\ErrorResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Обработка сообщений об ошибках.
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    private SerializerInterface $serializer;

    private TranslatorInterface $translator;

    public function __construct(
        LoggerInterface $logger,
        TranslatorInterface $translator,
        SerializerInterface $serializer
    ) {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->serializer = $serializer;
    }

    /**
     * @return \array[][]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'handleException',
        ];
    }

    /**
     * Обработка ответа с ошибкой.
     */
    public function handleException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        // Трансляция сообщений
        $message = $exception instanceof TranslatableInterface
            ? $this->translator->trans($exception->getMessage())
            : $exception->getMessage();

        // Детализация ошибок (для валидации)
        $errors = $exception instanceof ErrorDetailsInterface ? $exception->getErrorDetails() : [];

        // Код ответа
        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        // Переопределение параметров ответа от API-клиента
        if ($exception instanceof ApiClientExceptionInterface) {
            try {
                $jsonArray = $exception->getResponse()->toArray(false);
            } catch (\Throwable) {
                $jsonArray = [];
            }

            $errors = array_merge($errors, $jsonArray['errors'] ?? []);
            $message = $jsonArray['message'] ?? $message;
            $statusCode = $exception->getStatusCode();
        }

        $event->setResponse(
            (new ApiResponse($this->serializer))
                ->setResult(new ErrorResult($message, $errors))
                ->setStatusCode($statusCode)
        );

        $this->logger->error($message, [
            'trace_id' => Uuid::v4()->toRfc4122(),
            'error_details' => $errors,
            'exception' => $exception,
        ]);
    }
}
