<?php

declare(strict_types=1);

namespace App\Manager;

use Flagception\Manager\FeatureManagerInterface;

class FeatureManager
{
    public const IS_ENABLED_MONOLITH_KAFKA = 'is_enabled_monolith_kafka';

    public const IS_ENABLED_DELIVERY_TO_CUSTOMER = 'is_enabled_delivery_to_customer';

    public const IS_ENABLED_DISTRIBUTORS_RESERVES = 'is_enabled_distributors_reserves';

    public const IS_ENABLED_SENDING_TO_SNP = 'is_enabled_sending_to_snp';

    public const IS_ENABLED_APPLY_ITEMS_DISCOUNT = 'is_enabled_apply_items_discount';

    private FeatureManagerInterface $featureManager;

    public function __construct(FeatureManagerInterface $featureManager)
    {
        $this->featureManager = $featureManager;
    }

    /**
     * Включена ли обработка сообщений из монолита через кафку
     * // TODO выпилить после переезда на кафку.
     */
    public function isEnabledMonolithKafka(): bool
    {
        return $this->featureManager->isActive(self::IS_ENABLED_MONOLITH_KAFKA);
    }

    /**
     * Включена ли доставка до клиента.
     */
    public function isEnabledDeliveryToCustomer(): bool
    {
        return $this->featureManager->isActive(self::IS_ENABLED_DELIVERY_TO_CUSTOMER);
    }

    /**
     * Включена ли обработка резервов для заказов дистрибьюторам
     */
    public function isEnabledDistributorsReserves(): bool
    {
        return $this->featureManager->isActive(self::IS_ENABLED_DISTRIBUTORS_RESERVES);
    }

    /**
     * Включена отправка цен и остатков напрямую в SnP Adapter.
     */
    public function isEnabledSendingToSNP(): bool
    {
        return $this->featureManager->isActive(self::IS_ENABLED_SENDING_TO_SNP);
    }

    /**
     * Включено ли применение скидки на позиции.
     */
    public function isEnabledApplyItemsDiscount(): bool
    {
        return $this->featureManager->isActive(self::IS_ENABLED_APPLY_ITEMS_DISCOUNT);
    }
}
