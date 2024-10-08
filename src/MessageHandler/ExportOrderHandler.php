<?php

namespace App\MessageHandler;

use App\Manager\FeatureManager;
use App\Service\OrderExporterInterface;
use test1\Message\V2\ExportOrder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

/**
 *  Обработчик сообщений экспорта заказов в (УАС).
 */
class ExportOrderHandler implements MessageHandlerInterface, MessageSubscriberInterface
{
    protected FeatureManager $featureManager;
    private LoggerInterface $logger;

    private OrderExporterInterface $exporter;

    public function __construct(
        LoggerInterface $logger,
        OrderExporterInterface $exporter,
        FeatureManager $featureManager
    ) {
        $this->logger = $logger;
        $this->exporter = $exporter;
        $this->featureManager = $featureManager;
    }

    public function __invoke(ExportOrder $exportOrder)
    {
        if (!$this->featureManager->isEnabledMonolithKafka()) {
            return;
        }

        $this->handle($exportOrder);
    }

    protected function handle(ExportOrder $exportOrder)
    {
        $this->logger->info(sprintf('orders.export: handle %s message from IM', ExportOrder::class), [
            'request_id' => $exportOrder->getRequestId(),
            'order_id' => $exportOrder->getNumber(),
            'handler' => static::class, // TODO выпилить после переезда на кафку
        ]);

        try {
            $this->exporter->export($exportOrder);
        } catch (\Throwable $exception) {
            $this->logger->critical(
                sprintf(
                    'orders.export: failed export order #%s. %s',
                    $exportOrder->getNumber(),
                    $exception->getMessage()
                ),
                [
                    'exception' => $exception,
                    'request_id' => $exportOrder->getRequestId(),
                ]
            );

            // Сообщение будет перенесено в failed-очередь
            throw $exception;
        }
    }

    // TODO выпилить после переезда на кафку
    public static function getHandledMessages(): iterable
    {
        yield ExportOrder::class => [
            'from_transport' => 'orders_export_kafka',
        ];
    }
}
