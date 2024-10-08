<?php

namespace App\Service;

use App\Client\PROVIDER\ApiClient;
use App\Client\PROVIDER\v2\OrderDto;
use App\Client\PROVIDER\v2\OrderProductDto;
use App\Client\PROVIDER\v2\OrderRowDto;
use App\Entity\Order;
use App\Event\OrderExportEvent;
use App\Exception\ApiClientException;
use App\Manager\FeatureManager;
use App\Repository\OrderRepository;
use App\Request\Dto\OrderStatusInterface as ImOrderStatus;
use Exception;
use test1\Message\V2\ExportOrder;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Сервис заказов.
 */
class OrderService implements OrderExporterInterface
{
    public function __construct(
        private ApiClient $PROVIDERClient,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher,
        private FeatureManager $featureManager,
        private OrderStatusService $orderStatusService,
        private OrderRepository $orderRepository,
        private DiscountService $discountService
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function export(ExportOrder $exportOrder): void
    {
        $PROVIDERStatus = $this->orderStatusService->getPROVIDERStatusByImStatus($exportOrder->getStatus());
        if (is_null($PROVIDERStatus)) {
            throw new Exception(sprintf('For order #%s link statuses IM <-> PROVIDER with status=%s not found', $exportOrder->getNumber(), $exportOrder->getStatus()));
        }
        try {
            // Обновление заказа
            $exportOrder->getStatus() === ImOrderStatus::STATUS_CREATED
                ? $this->createOrder($exportOrder)
                : $this->updateOrder($exportOrder);

            // Обработка резервов
            $this->eventDispatcher->dispatch(new OrderExportEvent($exportOrder));

            if (in_array($exportOrder->getStatus(), ImOrderStatus::FINISHED_STATUSES, true)) {
                $this->orderRepository->deleteById($exportOrder->getNumber());
            }
        } catch (ApiClientException $exception) {
            $error = $exception->getResponse()->toArray(false)['Error'] ?? [];

            $this->logger->critical(
                sprintf('orders.export: failed request order #%s by PROVIDER.', $exportOrder->getNumber()),
                [
                    'PROVIDER_error' => $error,
                ]
            );

            throw $exception;
        }
    }

    /**
     * Создание нового заказа.
     */
    private function createOrder(ExportOrder $exportOrder): void
    {
        $exportOrder = $this->discountService->applyDiscount($exportOrder);

        $rows = [];

        foreach ($exportOrder->getItems() as $orderItem) {
            $orderRowDto = new OrderRowDto(
                $orderItem->getQuantity(),
                $orderItem->getReserved(),
                $orderItem->getSum(),
                $orderItem->getPrice(),
                new OrderProductDto(
                    $orderItem->getCode(),
                    $orderItem->getVendorCode(),
                    $orderItem->getBarcode()
                )
            );

            if ($this->featureManager->isEnabledDeliveryToCustomer()) {
                $orderRowDto->setRecipe($orderItem->getRecipeId());
            }

            $rows[] = $orderRowDto;
        }

        // Создание нового
        $orderDto = (new OrderDto(
            $exportOrder->getRequestId(),
            $exportOrder->getNumber(),
            $this->orderStatusService->getPROVIDERStatusByImStatus($exportOrder->getStatus()),
            $exportOrder->getType(),
            $rows
        ))
            ->setComment($exportOrder->getComment())
            ->setTypePay($exportOrder->getTypePay())
            ->setSumPay($exportOrder->getSumPay())
            ->setDivisionPost($exportOrder->getDivisionPost() ?: '')
            ->setDivision($exportOrder->getDivision() ?: '')
            ->setPhone($exportOrder->getPhone())
            ->setDateOrder($exportOrder->getDate())
            ->setDateProvidingOrder($exportOrder->getDateProviding() ?: '')
            ->setPartner($exportOrder->getPartnerName() ?: '');

        if ($this->featureManager->isEnabledDeliveryToCustomer()) {
            $orderDto->setAddressDelivery($exportOrder->getAddressDelivery());
        }
        if ($dateOrderExecution = $exportOrder->getDateOrderExecution()) {
            $orderDto->setDateOrderExecution($dateOrderExecution);
        }

        $result = $this->PROVIDERClient->createOrder($orderDto);

        $order = (new Order())
            ->setId((int) $exportOrder->getNumber())
            ->setIsDeliveryToCustomer((bool) $exportOrder->getAddressDelivery())
            ->setIsDistributor((bool) $exportOrder->getDateOrderExecution());

        $this->orderRepository->save($order);

        $this->logger->info(
            sprintf(
                'orders.export: create order request for order #%s accepted by PROVIDER',
                $exportOrder->getNumber()
            ),
            [
                'request_id' => $result->getId(),
                'message' => $result->getMessage(),
            ]
        );
    }

    /**
     * Обновление заказа.
     */
    private function updateOrder(ExportOrder $exportOrder): void
    {
        $orderDto = (new OrderDto(
            $exportOrder->getRequestId(),
            $exportOrder->getNumber(),
            $this->orderStatusService->getPROVIDERStatusByImStatus($exportOrder->getStatus()),
            $exportOrder->getType()
        ));

        if ($this->featureManager->isEnabledDeliveryToCustomer()) {
            $orderDto->setAcceptCode($exportOrder->getAcceptCode());
        }

        $result = $this->PROVIDERClient->updateOrder($orderDto);

        $this->logger->info(
            sprintf(
                'orders.export: update status request for order #%s accepted by PROVIDER',
                $exportOrder->getNumber()
            ),
            [
                'request_id' => $result->getId(),
                'message' => $result->getMessage(),
            ]
        );
    }
}
