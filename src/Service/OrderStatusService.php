<?php

namespace App\Service;

use App\Manager\FeatureManager;
use App\Repository\OrderRepository;
use App\Request\Dto\OrderStatusInterface as ImOrderStatus;
use App\Request\Dto\PROVIDER\OrderStatusInterface as PROVIDEROrderStatus;
use App\Request\Dto\PROVIDER\v2\OrderDto;
use App\Request\Dto\PROVIDER\v2\OrderStatusDto;

class OrderStatusService
{
    private array $imToPROVIDERMap;

    private array $PROVIDERToImMap;

    private FeatureManager $featureManager;

    private OrderRepository $orderRepository;

    public function __construct(
        FeatureManager $featureManager,
        OrderRepository $orderRepository
    ) {
        $imToPROVIDERMap = [
            ImOrderStatus::STATUS_CREATED => PROVIDEROrderStatus::STATUS_CREATED,
            ImOrderStatus::STATUS_CANCELLED => PROVIDEROrderStatus::STATUS_CANCELED,
            ImOrderStatus::STATUS_NOT_REDEEMED => PROVIDEROrderStatus::STATUS_CANCELED,
            ImOrderStatus::STATUS_LET_OUT => PROVIDEROrderStatus::STATUS_READY,
            ImOrderStatus::STATUS_CONFIRMATION => PROVIDEROrderStatus::STATUS_PART_READY,
            ImOrderStatus::STATUS_ASSEMBLING => PROVIDEROrderStatus::STATUS_ASSEMBLING,
            ImOrderStatus::STATUS_FINISHED => PROVIDEROrderStatus::STATUS_COMPLETED,
            ImOrderStatus::STATUS_WAIT_MDLP => PROVIDEROrderStatus::STATUS_WAIT_MDLP,
        ];

        if ($featureManager->isEnabledDeliveryToCustomer()) {
            $imToPROVIDERMap[ImOrderStatus::STATUS_READY_TO_COURIER] = PROVIDEROrderStatus::STATUS_READY;
            $imToPROVIDERMap[ImOrderStatus::STATUS_TRANSFERRED_TO_COURIER] = PROVIDEROrderStatus::STATUS_READY;
            $imToPROVIDERMap[ImOrderStatus::STATUS_WAITING_OF_RETURN] = PROVIDEROrderStatus::STATUS_WAITING_OF_RETURN;
            $imToPROVIDERMap[ImOrderStatus::STATUS_LOST_BY_COURIER] = PROVIDEROrderStatus::STATUS_LOST_BY_COURIER;
            $imToPROVIDERMap[ImOrderStatus::STATUS_NON_PURCHASE_ACCEPTED] = PROVIDEROrderStatus::STATUS_NON_PURCHASE_ACCEPTED;
            $imToPROVIDERMap[ImOrderStatus::STATUS_WAITING_OF_COURIER] = PROVIDEROrderStatus::STATUS_WAITING_OF_COURIER;
        }

        $this->imToPROVIDERMap = $imToPROVIDERMap;

        $PROVIDERToImMap = [
            PROVIDEROrderStatus::STATUS_ASSEMBLING => ImOrderStatus::STATUS_ASSEMBLING,
            PROVIDEROrderStatus::STATUS_PART_READY => ImOrderStatus::STATUS_CONFIRMATION,
            PROVIDEROrderStatus::STATUS_READY => ImOrderStatus::STATUS_LET_OUT,
            PROVIDEROrderStatus::STATUS_WAIT_MDLP => ImOrderStatus::STATUS_WAIT_MDLP,
            PROVIDEROrderStatus::STATUS_ON_TRADES => ImOrderStatus::STATUS_ON_TRADES,
        ];

        if ($featureManager->isEnabledDeliveryToCustomer()) {
            $PROVIDERToImMap[PROVIDEROrderStatus::STATUS_TRANSFERRED_TO_COURIER] = ImOrderStatus::STATUS_TRANSFERRED_TO_COURIER;
            $PROVIDERToImMap[PROVIDEROrderStatus::STATUS_NON_PURCHASE_ACCEPTED] = ImOrderStatus::STATUS_NON_PURCHASE_ACCEPTED;
            $PROVIDERToImMap[PROVIDEROrderStatus::STATUS_NON_PURCHASE_PARTIALLY_ACCEPTED] = ImOrderStatus::STATUS_NON_PURCHASE_PARTIALLY_ACCEPTED;
            $PROVIDERToImMap[PROVIDEROrderStatus::STATUS_RECIPE_COMPLETED] = ImOrderStatus::STATUS_RECIPE_COMPLETED;
        }

        $this->PROVIDERToImMap = $PROVIDERToImMap;
        $this->featureManager = $featureManager;
        $this->orderRepository = $orderRepository;
    }

    public function getPROVIDERStatusByImStatus(string $imStatus): ?int
    {
        return $this->imToPROVIDERMap[$imStatus] ?? null;
    }

    public function getImStatusByPROVIDERStatus(string $orderId, int $PROVIDERStatus): ?string
    {
        $orderEntity = null;
        if ($this->featureManager->isEnabledDeliveryToCustomer()) {
            $orderEntity = $this->orderRepository->find($orderId);
        }
        if ($PROVIDERStatus === PROVIDEROrderStatus::STATUS_READY && $orderEntity?->isDeliveryToCustomer()) {
            return ImOrderStatus::STATUS_READY_TO_COURIER;
        }

        return $this->PROVIDERToImMap[$PROVIDERStatus] ?? null;
    }

    public function getImStatusByOrderStatusDto(OrderStatusDto $orderStatusDto): ?string
    {
        return $this->getImStatusByPROVIDERStatus($orderStatusDto->getOrder(), $orderStatusDto->getStatus());
    }

    public function getImStatusByOrderDto(OrderDto $orderDto): ?string
    {
        return $this->getImStatusByPROVIDERStatus($orderDto->getNumber(), $orderDto->getStatus());
    }
}
