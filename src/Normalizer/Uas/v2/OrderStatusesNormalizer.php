<?php

namespace App\Normalizer\PROVIDER\v2;

use App\Request\Dto\PROVIDER\v2\OrderStatusesDto;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Денормализатор данных о статусах заказов из УАС v2.
 */
class OrderStatusesNormalizer extends ObjectNormalizer
{
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return $type === OrderStatusesDto::class
            && parent::supportsDenormalization($data, $type, $format);
    }

    public function denormalize($data, string $type, string $format = null, array $context = []): OrderStatusesDto
    {
        $normalizedData = [
            'Id' => (string) ($data['Id'] ?? $data['id'] ?? null),
            'Time' => (string) ($data['Time'] ?? $data['time'] ?? null),
            'Orders' => [],
        ];

        $orders = $data['Orders'] ?? $data['orders'] ?? [];

        if ($orders) {
            foreach ($orders as $order) {
                $status = (int) ($order['Status'] ?? $order['status'] ?? null);
                $date = (string) ($order['Date'] ?? $order['date'] ?? null);

                $normalizedData['Orders'][] = [
                    'Order' => (string) ($order['Order'] ?? $order['order'] ?? null),
                    'Status' => $status ?: null,
                    'Date' => $date ?: null,
                ];
            }
        }

        return parent::denormalize($normalizedData, $type, $format, $context);
    }
}
