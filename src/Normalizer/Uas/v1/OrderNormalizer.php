<?php

namespace App\Normalizer\PROVIDER\v1;

use App\Request\Dto\PROVIDER\v1\OrderDto;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Денормализатор данных по заказам из УАС v1.
 */
class OrderNormalizer extends ObjectNormalizer
{
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return $type === OrderDto::class
            && parent::supportsDenormalization($data, $type, $format);
    }

    public function denormalize($data, string $type, string $format = null, array $context = []): OrderDto
    {
        $normalizedData = [
            'Status' => (int) ($data['Status'] ?? null),
            'TypeOrder' => (int) ($data['TypeOrder'] ?? null),
            'Number' => (string) ($data['Number'] ?? null),
            'UserEdit' => (string) ($data['UserEdit'] ?? null),
            'TimeEdit' => (string) ($data['TimeEdit'] ?? null),
            'Rows' => [],
        ];

        if ($rows = $data['Rows'] ?? null) {
            foreach ($rows as $row) {
                $normalizedProduct = [
                    'Name' => (string) ($row['Product']['Name'] ?? null),
                    'Code' => (string) ($row['Product']['Code'] ?? null),
                ];

                $normalizedData['Rows'][] = [
                    'Quantity' => (int) ($row['Quantity'] ?? null),
                    'Reserved' => (int) ($row['Reserved'] ?? null),
                    // 'Price' => (float) ($row['Price'] ?? null),
                    'Sum' => (float) ($row['Sum'] ?? null),
                    'Product' => $normalizedProduct,
                ];
            }
        }

        return parent::denormalize($normalizedData, $type, $format, $context);
    }
}
