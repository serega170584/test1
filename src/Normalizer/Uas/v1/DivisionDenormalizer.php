<?php

namespace App\Normalizer\PROVIDER\v1;

use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;

/**
 * Денормализатор данных по остаткам из УАС v1.
 */
final class DivisionDenormalizer extends ArrayDenormalizer
{
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return $type === 'App\Request\Dto\PROVIDER\v1\DivisionDto[]'
            && parent::supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * Денормализация кривых данных от УАС.
     * От УАС имя поля приходит в верхнем регистре, а в идеале должно быть в нижнем (как в FakeApi).
     * Поддерживаем 2 варианта.
     *
     * @param mixed $data
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): array
    {
        foreach ($data as $key => $division) {
            $normalizedDivision = [
                'Division' => (string) ($division['Division'] ?? $division['division'] ?? null),
                'items' => [],
            ];

            foreach ($division['items'] ?? [] as $itemKey => $itemVal) {
                if (!is_null($price = $itemVal['Price'] ?? $itemVal['price'] ?? null)) {
                    $price = is_numeric($price) ? (float) $price : null;
                }

                if (!is_null($quantity = $itemVal['Quantity'] ?? $itemVal['quantity'] ?? null)) {
                    $quantity = is_numeric($quantity) ? (int) $quantity : null;
                }

                $normalizedDivision['items'][$itemKey] = [
                    'Code' => (string) ($itemVal['Code'] ?? $itemVal['code'] ?? null),
                    'Price' => $price,
                    'Quantity' => $quantity,
                ];
            }

            $data[$key] = $normalizedDivision;
        }

        return parent::denormalize($data, $type, $format, $context);
    }
}
