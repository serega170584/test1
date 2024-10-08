<?php

namespace App\Normalizer\PROVIDER\v2;

use App\Request\Dto\PROVIDER\v2\OrderDto;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Денормализатор данных по заказам из УАС v2.
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
            'Id' => (string) ($data['Id'] ?? null),
            'Status' => (int) ($data['Status'] ?? null),
            'TypeOrder' => (int) ($data['TypeOrder'] ?? null),
            'Number' => (string) ($data['Number'] ?? null),
            'UserEdit' => isset($data['UserEdit']) ? (string) $data['UserEdit'] : null,
            'TimeEdit' => isset($data['TimeEdit']) ? (string) $data['TimeEdit'] : null,
            'Sid' => isset($data['Sid']) ? (string) $data['Sid'] : null,
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
                    'Sum' => (float) ($row['Sum'] ?? null),
                    'RecipeConfirm' => isset($row['RecipeConfirm']) ? (int) $row['RecipeConfirm'] : null,
                    'MarkingCodes' => isset($row['MarkingCodes']) ? (array) $row['MarkingCodes'] : null,
                    'Product' => $normalizedProduct,
                ];
            }
        }

        return parent::denormalize($normalizedData, $type, $format, $context);
    }
}
