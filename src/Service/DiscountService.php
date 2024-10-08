<?php
declare(strict_types=1);

namespace App\Service;

use App\Manager\FeatureManager;
use test1\Message\V2\ExportOrder;
use test1\Message\V2\ExportOrderItem;

class DiscountService
{
    public function __construct(
        private readonly FeatureManager $featureManager
    ) {
    }

    /**
     * Вычисляет и применяет скидку к позициям заказа,
     * т.к. в УАС обрабатываются позиции и игнорируется sumPay заказа, а в заказах из платформы sumPay со скидкой, а позиции без скидки.
     */
    public function applyDiscount(ExportOrder $exportOrder): ExportOrder
    {
        if (!$this->featureManager->isEnabledApplyItemsDiscount()) {
            return $exportOrder;
        }
        // У заказов, созданных по старому флоу в sumPay приходит 0
        if (!$exportOrder->getSumPay()) {
            return $exportOrder;
        }

        $sumPayByItems = 0;
        foreach ($exportOrder->getItems() as $item) {
            $sumPayByItems += $item->getSum();
        }

        if ($sumPayByItems <= $exportOrder->getSumPay()) {
            return $exportOrder;
        }

        $discountRatio = $exportOrder->getSumPay() / $sumPayByItems;

        $itemsWithDiscount = [];
        foreach ($exportOrder->getItems() as $item) {
            $itemsWithDiscount[] = (new ExportOrderItem(
                $item->getCode(),
                $item->getQuantity(),
                $item->getReserved(),
                $item->getPrice() * $discountRatio
            ))
            ->setBarcode($item->getBarcode())
            ->setRecipeId($item->getRecipeId())
            ->setVendorCode($item->getVendorCode());
        }
        $exportOrder->setItems($itemsWithDiscount);

        return $exportOrder;
    }
}
