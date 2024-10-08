<?php

namespace App\Normalizer\PROVIDER\v1;

use App\Request\Dto\PROVIDER\v1\OrderReceiptDto;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Денормализатор данных о чеках из УАС v1.
 */
class OrderReceiptNormalizer extends ObjectNormalizer
{
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return $type === OrderReceiptDto::class
            && parent::supportsDenormalization($data, $type, $format);
    }

    public function denormalize($data, string $type, string $format = null, array $context = []): OrderReceiptDto
    {
        $normalizedData = [
            'Type' => (int) ($data['Type'] ?? OrderReceiptDto::TYPE_SALE),
            'Order' => (string) ($data['Order'] ?? null),
            'LoyaltyCard' => [],
            'Rows' => [],
        ];

        if ($cards = $data['LoyaltyCard'] ?? null) {
            foreach ($cards as $card) {
                $normalizedData['LoyaltyCard'][] = (string) $card;
            }
        }

        if ($rows = $data['Rows'] ?? null) {
            foreach ($rows as $row) {
                $normalizedProduct = [
                    'Name' => (string) ($row['Product']['Name'] ?? null),
                    'Code' => (string) ($row['Product']['Code'] ?? null),
                ];

                $normalizedData['Rows'][] = [
                    'Quantity' => (int) ($row['Quantity'] ?? null),
                    // 'Price' => (float) ($row['Price'] ?? null),
                    'Sum' => (float) ($row['Sum'] ?? null),
                    'Product' => $normalizedProduct,
                ];
            }
        }

        return parent::denormalize($normalizedData, $type, $format, $context);
    }
}
