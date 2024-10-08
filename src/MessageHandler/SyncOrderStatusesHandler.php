<?php

namespace App\MessageHandler;

use App\Client\PROVIDER\ApiClient;
use App\Client\PROVIDER\OrderStatusesDto;
use App\Manager\FeatureManager;
use test1\Message\V2\SyncOrderStatuses;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

/**
 * Обработчик сообщений запроса статусов заказов из внешних систем (УАС).
 */
class SyncOrderStatusesHandler implements MessageHandlerInterface, MessageSubscriberInterface
{
    protected LoggerInterface $logger;

    protected FeatureManager $featureManager;
    private ApiClient $apiClient;

    public function __construct(
        LoggerInterface $logger,
        ApiClient $apiClient,
        FeatureManager $featureManager
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
        $this->featureManager = $featureManager;
    }

    public function __invoke(SyncOrderStatuses $orderStatuses)
    {
        if (!$this->featureManager->isEnabledMonolithKafka()) {
            return;
        }

        $this->handle($orderStatuses);
    }

    protected function handle(SyncOrderStatuses $orderStatuses)
    {
        $this->logger->info(sprintf('orders.sync_statuses: handle %s message from IM', SyncOrderStatuses::class), [
            'request_id' => $orderStatuses->getRequestId(),
            'orders' => $orderStatuses->getOrders(),
            'handler' => static::class, // TODO выпилить после переезда на кафку
        ]);

        try {
            // Запрос в УАС
            $result = $this->apiClient->requestStatuses(
                new OrderStatusesDto($orderStatuses->getRequestId(), $orderStatuses->getOrders())
            );

            $this->logger->info('orders.sync_statuses: order statuses request accepted by PROVIDER', [
                'request_id' => $result->getId(),
                'request_time' => $result->getTime(),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->critical('orders.sync_statuses: failed send order statuses request in PROVIDER', [
                'exception' => $exception,
                'request_id' => $orderStatuses->getRequestId(),
            ]);

            // Сообщение будет перенесено в failed-очередь
            throw $exception;
        }
    }

    // TODO выпилить после переезда на кафку
    public static function getHandledMessages(): iterable
    {
        yield SyncOrderStatuses::class => [
            'from_transport' => 'orders_sync_statuses_kafka',
        ];
    }
}
