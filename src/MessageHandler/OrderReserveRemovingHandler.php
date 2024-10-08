<?php

namespace App\MessageHandler;

use App\Message\OrderReserveRemoving;
use App\Service\OrderReserveManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Обработчик сообщений с отложенным удалением резервов.
 */
class OrderReserveRemovingHandler implements MessageHandlerInterface
{
    private OrderReserveManagerInterface $reserveManager;

    private LoggerInterface $logger;

    public function __construct(OrderReserveManagerInterface $reserveManager, LoggerInterface $logger)
    {
        $this->reserveManager = $reserveManager;
        $this->logger = $logger;
    }

    public function __invoke(OrderReserveRemoving $orderReserve)
    {
        $this->logger->info(sprintf('reserves.handle: handle %s message', OrderReserveRemoving::class), [
            'order_id' => $orderReserve->getOrderId(),
        ]);

        try {
            // Удаление резерва без отправки в монолит
            $this->reserveManager->removeOrderReservesByOrderId($orderReserve->getOrderId());
        } catch (\Throwable $exception) {
            $this->logger->critical(
                sprintf(
                    'reserves.handle: failed remove reserves for order #%s. %s',
                    $orderReserve->getOrderId(),
                    $exception->getMessage()
                ),
                [
                    'exception' => $exception,
                ]
            );

            throw $exception;
        }
    }
}
