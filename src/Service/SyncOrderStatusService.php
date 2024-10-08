<?php
declare(strict_types=1);

namespace App\Service;

use App\Client\PROVIDER\ApiClient;
use App\Client\PROVIDER\v2\OrderStatusItem;
use App\Client\PROVIDER\v2\SendSyncOrdersStatuses;
use App\Exception\SyncOrderStatusException;
use App\Exception\ValidationException;
use App\Request\Dto\PROVIDER\v2\RequestSyncOrderStatuses;
use JsonException;
use test1\Message\V2\RequestSyncOrderStatuses as RequestSyncOrderStatusesMessage;
use test1\Message\V2\ResponseSyncOrderStatuses;
use test1\Message\V2\SyncOrderStatusItem;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

class SyncOrderStatusService
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly OrderStatusService $orderStatusService,
        private readonly ApiClient $apiClient,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * @throws SyncOrderStatusException
     * @throws JsonException
     */
    public function sendRequestOrdersStatusesToIM(RequestSyncOrderStatuses $request): void
    {
        $this->logger->info(
            sprintf(
                '[%s] Начинаем отправку запроса на синк статусов заказо [%s]',
                'PROVIDER.sync.statuses.sendToIM',
                $request->getId()
            ),
        );

        if (($errors = $this->validator->validate($request)) && $errors->count()) {
            throw new ValidationException($errors);
        }

        try {
            $this->messageBus->dispatch(
                new RequestSyncOrderStatusesMessage($request->getId(), $request->getOrders())
            );
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    '[%s] При попытке синка статусов заказов произошла ошибка [%s]',
                    'PROVIDER.sync.statuses.sendToIM',
                    $request->getId()
                ),
                [
                    'orders' => json_encode($request->getOrders(), JSON_THROW_ON_ERROR),
                    'exception' => $e,
                ]
            );

            throw new SyncOrderStatusException('При попытке синка статусов заказов произошла ошибка', Response::HTTP_INTERNAL_SERVER_ERROR, $e);
        }
    }

    /**
     * @throws SyncOrderStatusException
     */
    public function sendOrdersStatusesToPROVIDER(ResponseSyncOrderStatuses $responseSyncOrderStatuses): void
    {
        try {
            $this->logger->info(
                sprintf(
                    '[%s] Начинаем отправку статусов заказов ИМ в УАС [%s]',
                    'PROVIDER.sync.statuses.sendToPROVIDER',
                    $responseSyncOrderStatuses->getRequestId()
                )
            );

            $request = new SendSyncOrdersStatuses($responseSyncOrderStatuses->getRequestId());

            foreach ($responseSyncOrderStatuses->getOrders() as $orderItem) {
                $request->addOrderItem(
                    new OrderStatusItem(
                        $orderItem->getOrder(),
                        $this->convertImStatusToPROVIDERStatus($responseSyncOrderStatuses->getRequestId(), $orderItem)
                    )
                );
            }

            $this->apiClient->sendSyncOrdersStatuses($request);

            $this->logger->info(
                sprintf(
                    '[%s] Отправку статусов заказов ИМ в УАС успешно завершилась [%s]',
                    'PROVIDER.sync.statuses.sendToPROVIDER',
                    $responseSyncOrderStatuses->getRequestId()
                )
            );
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    '[%s] При отправке статусов заказов из ИМ в УАС произошла ошибка [%s]',
                    'PROVIDER.sync.statuses.sendToPROVIDER',
                    $responseSyncOrderStatuses->getRequestId()
                ),
                [
                    'exception' => $e,
                ]
            );

            throw new SyncOrderStatusException('При отправке статусов заказов ИМ в УАС произошла ошибка', Response::HTTP_INTERNAL_SERVER_ERROR, $e);
        }
    }

    private function convertImStatusToPROVIDERStatus(string $requestId, SyncOrderStatusItem $orderStatusItem): ?int
    {
        if ($orderStatusItem->getStatus() === null) {
            $this->logger->warning(
                sprintf(
                    '[%s] для заказа #%s не передан статус ИМ [%s]',
                    'PROVIDER.sync.statuses.sendToPROVIDER',
                    $orderStatusItem->getOrder(),
                    $requestId
                )
            );

            return null;
        }

        $PROVIDEROrderStatus = $this->orderStatusService->getPROVIDERStatusByImStatus($orderStatusItem->getStatus());

        if ($PROVIDEROrderStatus === null) {
            $this->logger->warning(
                sprintf(
                    '[%s] для статуса ИМ заказа [%s] с ид #%s не найден маппинг со статусами УАС [%s]',
                    'PROVIDER.sync.statuses.sendToPROVIDER',
                    $orderStatusItem->getStatus(),
                    $orderStatusItem->getOrder(),
                    $requestId
                )
            );
        }

        return $PROVIDEROrderStatus;
    }
}
