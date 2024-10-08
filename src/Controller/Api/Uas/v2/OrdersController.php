<?php

namespace App\Controller\Api\PROVIDER\v2;

use App\Client\Monolith\PROVIDER\ApiClient;
use App\Event\OrderImportEvent;
use App\Exception\ValidationException;
use App\Manager\FeatureManager;
use App\Repository\OrderRepository;
use App\Request\Dto\OrderStatusInterface as ImOrderStatus;
use App\Request\Dto\PROVIDER\OrderStatusInterface as PROVIDEROrderStatus;
use App\Request\Dto\PROVIDER\v2\OrderDto;
use App\Request\Dto\PROVIDER\v2\OrderReceiptDto;
use App\Request\Dto\PROVIDER\v2\OrderResultDto;
use App\Request\Dto\PROVIDER\v2\OrderStatusesDto;
use App\Request\Dto\PROVIDER\v2\RequestSyncOrderStatuses;
use App\Response\Result\ErrorResult;
use App\Response\Result\SuccessResult;
use App\Response\SyncOrderStatusSuccessResponse;
use App\Service\OrderReserveManager;
use App\Service\OrderStatusService;
use App\Service\SyncOrderStatusService;
use FOS\RestBundle\Controller\Annotations as Rest;
use test1\Message\V2\ConfirmExportedOrder;
use test1\Message\V2\ImportOrder;
use test1\Message\V2\ImportOrderItem;
use test1\Message\V2\ImportOrderReceipt;
use test1\Message\V2\ImportOrderReceiptItem;
use test1\Message\V2\ImportOrderStatus;
use test1\Messenger\Transport\KafkaStamp;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * API v2 заказов.
 *
 * @Route("/v2/orders", name="_v2_orders")
 *
 * @OA\Response(
 *     response=200,
 *     description="Успешная обработка",
 *     @Model(type=SuccessResult::class)
 * )
 * @OA\Response(
 *     response=400,
 *     description="Ошибка валидации",
 *     @Model(type=ErrorResult::class)
 * )
 * @OA\Response(
 *     response=401,
 *     description="Ошибка авторизации",
 *     @Model(type=ErrorResult::class)
 * )
 * @OA\Response(
 *     response=500,
 *     description="Внутренняя ошибка сервера",
 *     @Model(type=ErrorResult::class)
 * )
 * @OA\Tag(name="orders")
 */
class OrdersController extends AbstractController
{
    /**
     * Подтверждение экспорта заказа.
     *
     * @Rest\Post("/confirm", name="_confirm")
     * @ParamConverter("orderResult", converter="fos_rest.request_body")
     *
     * @OA\RequestBody(
     *     description="Подтверждение обработки заказа",
     *     required=true,
     *     @OA\JsonContent(ref=@Model(type=OrderResultDto::class))
     * )
     * @OA\Tag(name="confirm")
     */
    public function confirm(
        OrderResultDto $orderResult,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        ApiClient $apiClient,
        Request $request
    ): JsonResponse {
        // Что-то не так с заказом
        if ($orderResult->getError()) {
            $logger->warning(
                sprintf(
                    'orders.export: something wrong with order #%s by processing in PROVIDER. %s',
                    $orderResult->getOrderId(),
                    $orderResult->getError()->getMessage()
                ),
                [
                    'request_id' => $orderResult->getId(),
                    'error_code' => $orderResult->getError()->getCode(),
                ]
            );

            return $this->json(new SuccessResult($translator->trans('response.orders.confirm_success')));
        }

        $logger->info(
            sprintf('orders.export: order #%s successful exported in PROVIDER', $orderResult->getOrderId()),
            [
                'request_id' => $orderResult->getId(),
                'message' => $orderResult->getMessage(),
            ]
        );

        $exportedOrder = (new ConfirmExportedOrder($orderResult->getOrderId(), $orderResult->getMessage()))
            ->setRequestId($orderResult->getId());

        $apiClient->confirmOrder($exportedOrder, [
            // ApiClient::PROXY_HEADER_AUTH => $request->server->get('HTTP_AUTHORIZATION'),
        ]);

        return $this->json(new SuccessResult($translator->trans('response.orders.confirm_success')));
    }

    /**
     * Обновление статусов заказов.
     *
     * @Rest\Put("/status", name="_statuses")
     * @Rest\Put("/update-status", name="_update_statuses")
     * @ParamConverter("orderStatuses", converter="fos_rest.request_body", options={"validator": {"groups": {"Default"}}})
     *
     * @OA\RequestBody(
     *     description="Информация о статусах заказов",
     *     required=true,
     *     @OA\JsonContent(ref=@Model(type=OrderStatusesDto::class))
     * )
     * @OA\Tag(name="status")
     */
    public function statuses(
        OrderStatusesDto $orderStatuses,
        ConstraintViolationListInterface $validationErrors,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        MessageBusInterface $bus,
        OrderStatusService $orderStatusService,
        FeatureManager $featureManager
    ): JsonResponse {
        if (count($validationErrors)) {
            throw new ValidationException($validationErrors);
        }

        $logData = [
            'request_id' => $orderStatuses->getId(),
            'request_time' => $orderStatuses->getTime(),
        ];

        $logger->info('orders.import_statuses: handle order statuses from PROVIDER', $logData);

        foreach ($orderStatuses->getOrders() as $order) {
            $logData['order_id'] = $order->getOrder();
            $logData['status'] = $order->getStatus();

            if (!$order->getStatus()) {
                $logger->notice(
                    sprintf('orders.import_statuses: unknown order #%s in PROVIDER', $order->getOrder()),
                    $logData
                );

                // Пропускаем несуществующие заказы в УАС
                continue;
            }

            if (PROVIDEROrderStatus::STATUS_NULLIFIED === $order->getStatus()) {
                // Для статусов УАС "аннулировано" ничего не делаем, пропускаем такой заказ
                // у нас нет соответствующего статуса в ИМ
                continue;
            }

            $imStatus = $orderStatusService->getImStatusByOrderStatusDto($order);

            if (!$imStatus) {
                $logger->warning(
                    sprintf(
                        'orders.import_statuses: unknown IM status for order #%s with status=%s',
                        $order->getOrder(),
                        $order->getStatus()
                    ),
                    $logData
                );

                // Пропускаем с неизвестным статусом
                continue;
            }

            // Статусы при которых нужно обновлять заказ в ИМ
            $orderUpdateStatuses = [
                ImOrderStatus::STATUS_CONFIRMATION,
            ];

            if ($featureManager->isEnabledDeliveryToCustomer()) {
                $orderUpdateStatuses[] = ImOrderStatus::STATUS_READY_TO_COURIER;
            }
            if (in_array($imStatus, $orderUpdateStatuses, true)) {
                $logger->warning(
                    'orders.import_statuses: skip order status',
                    $logData
                );

                // Пропускаются заказы со статусом, который предполагает товарную часть
                continue;
            }

            // Отправляем в очередь
            $message = (new ImportOrderStatus($order->getOrder(), $imStatus))
                ->setRequestId($orderStatuses->getId());

            $bus->dispatch($message, [
                new AmqpStamp('import-order-all'), // TODO выпилить после переезда на кафку
                new KafkaStamp($order->getOrder()),
            ]);
        }

        return $this->json(new SuccessResult($translator->trans('response.orders.status_success')));
    }

    /**
     * Обновление статуса заказа.
     *
     * @Rest\Patch("/status", name="_status")
     * @Rest\Patch("/update-status", name="_update_status")
     * @ParamConverter("order", converter="fos_rest.request_body", options={"validator": {"groups": {"status", "Default"}}})
     *
     * @OA\RequestBody(
     *     description="Информация о заказе",
     *     required=true,
     *     @OA\JsonContent(ref=@Model(type=OrderDto::class))
     * )
     * @OA\Tag(name="status")
     */
    public function status(
        OrderDto $order,
        ConstraintViolationListInterface $validationErrors,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        MessageBusInterface $bus,
        EventDispatcherInterface $eventDispatcher,
        FeatureManager $featureManager,
        OrderStatusService $orderStatusService
    ): JsonResponse {
        if (count($validationErrors)) {
            throw new ValidationException($validationErrors);
        }

        $logData = [
            'order_id' => $order->getNumber(),
            'request_id' => $order->getId(),
        ];

        $logger->info('orders.update_status: handle order from PROVIDER', $logData);

        if (PROVIDEROrderStatus::STATUS_NULLIFIED === $order->getStatus()) {
            // Для статусов УАС "аннулировано" ничего не делаем, пропускаем такой заказ
            // у нас нет соответствующего статуса в ИМ
            $logger->info('orders.update_status: skip order with nullified status', $logData);

            return $this->json(new SuccessResult($translator->trans('response.orders.status_success')));
        }

        $imStatus = $orderStatusService->getImStatusByOrderDto($order);

        // Если нет линковки статусов УАС - ИМ
        if (!$imStatus) {
            throw new BadRequestHttpException(sprintf('orders.update_status: Неизвестный статус ИМ для заказа #%s со статусом=%s в запросе #%s', $order->getNumber(), $order->getStatus(), $order->getId()), null, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Статусы при которых нужно обновлять заказ в ИМ
        $orderUpdateStatuses = [
            ImOrderStatus::STATUS_CONFIRMATION,
        ];

        if ($featureManager->isEnabledDeliveryToCustomer()) {
            $orderUpdateStatuses[] = ImOrderStatus::STATUS_READY_TO_COURIER;
        }

        if (in_array($imStatus, $orderUpdateStatuses, true)) {
            $items = [];
            foreach ($order->getRows() as $row) {
                $importOrderItem = new ImportOrderItem(
                    $row->getQuantity(),
                    $row->getSum(),
                    $row->getProduct()->getCode(),
                    $row->getReserved(
                    ) // TODO разобраться почему в конструкторе не возможен null, а в DTO возможен null
                );

                if ($featureManager->isEnabledDeliveryToCustomer()) {
                    $importOrderItem
                        ->setMarkingCodes($row->getMarkingCodes())
                        ->setRecipeConfirm($row->getRecipeConfirm());
                }

                $items[] = $importOrderItem;
            }
            $message = new ImportOrder(
                $order->getNumber(),
                $imStatus,
                $items,
                $order->getUserEdit(),
                $order->getTimeEdit()
            );
            if ($featureManager->isEnabledDeliveryToCustomer()) {
                $message
                    ->setSid($order->getSid());
            }
        } else {
            // Обновление статуса заказа
            $message = new ImportOrderStatus(
                $order->getNumber(),
                $imStatus,
                $order->getUserEdit(),
                $order->getTimeEdit()
            );
        }

        $message->setRequestId($order->getId());

        $bus->dispatch($message, [
            new AmqpStamp('import-order-all'), // TODO выпилить после переезда на кафку
            new KafkaStamp($order->getNumber()),
        ]);

        $logger->info(
            'orders.update_status: order successful exported in IM',
            $logData
        );

        // Обработка резервов
        $eventDispatcher->dispatch(
            new OrderImportEvent($order->getNumber(), $order->getStatus(), $order->getTypeOrder())
        );

        return $this->json(new SuccessResult($translator->trans('response.orders.status_success')));
    }

    /**
     * Выдача чека.
     *
     * @Rest\Patch("/complete", name="_complete")
     * @Rest\Patch("/complete-order", name="_complete_order")
     * @ParamConverter("orderReceipt", converter="fos_rest.request_body", options={"validator": {"groups": {"complete", "Default"}}})
     *
     * @OA\RequestBody(
     *     description="Чек заказа",
     *     required=true,
     *     @OA\JsonContent(ref=@Model(type=OrderReceiptDto::class))
     * )
     * @OA\Tag(name="complete")
     */
    public function complete(
        OrderReceiptDto $orderReceipt,
        ConstraintViolationListInterface $validationErrors,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        MessageBusInterface $bus,
        OrderRepository $orderRepository,
        OrderReserveManager $orderReserveManager
    ): JsonResponse {
        if (count($validationErrors)) {
            throw new ValidationException($validationErrors);
        }

        $logData = [
            'request_id' => $orderReceipt->getId(),
        ];

        $logger->info(
            sprintf('orders.complete: handle receipt for order #%s from PROVIDER', $orderReceipt->getOrder()),
            $logData
        );

        $items = [];
        foreach ($orderReceipt->getRows() as $row) {
            $items[] = new ImportOrderReceiptItem(
                $row->getQuantity(),
                $row->getSum(),
                $row->getProduct()->getCode(),
                $row->getProduct()->getName()
            );
        }

        $message = (new ImportOrderReceipt(
            $orderReceipt->getOrder(),
            $orderReceipt->getType(),
            $items,
            $orderReceipt->getLoyaltyCard()
        ))->setRequestId($orderReceipt->getId());

        $bus->dispatch($message, [
            new AmqpStamp('import-order-all'), // TODO выпилить после переезда на кафку
            new KafkaStamp($orderReceipt->getOrder()),
        ]);

        $logger->info(
            sprintf('orders.complete: receipt for order #%s successful exported in IM', $orderReceipt->getOrder()),
            $logData
        );

        $orderEntity = $orderRepository->find($orderReceipt->getOrder());
        if ($orderEntity) {
            if ($orderEntity->isDistributor()) {
                $orderReserveManager->dispatchRemoving((int) $orderReceipt->getOrder());
            }
            $orderRepository->delete($orderEntity);
        }

        return $this->json(new SuccessResult($translator->trans('response.orders.complete_success')));
    }

    /**
     * Синхранизация статусов заказа.
     *
     * @Rest\Post("/sync-status", name="_sync-status")
     * @ParamConverter("requestSyncOrderStatuses", converter="fos_rest.request_body")
     *
     * @OA\RequestBody(
     *     description="Синхранизация статусов заказа",
     *     required=true,
     *     @OA\JsonContent(ref=@Model(type=RequestSyncOrderStatuses::class))
     * )
     * @OA\Tag(name="sync-status")
     */
    public function syncStatus(
        TranslatorInterface $translator,
        RequestSyncOrderStatuses $requestSyncOrderStatuses,
        SyncOrderStatusService $syncOrderStatusService
    ): Response {
        $syncOrderStatusService->sendRequestOrdersStatusesToIM($requestSyncOrderStatuses);

        return $this->json(
            new SyncOrderStatusSuccessResponse(
                $requestSyncOrderStatuses->getId(),
                $translator->trans('response.orders.sync_status')
            )
        );
    }
}
