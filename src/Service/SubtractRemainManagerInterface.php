<?php

declare(strict_types=1);

namespace App\Service;

use App\Request\Dto\PROVIDER\v1\DivisionDto;

interface SubtractRemainManagerInterface
{
    /**
     * Подсчитать количество, которое будет вычитаться из общего остатка.
     * Суммируются резервы и минимальные остатки.
     *
     * @param DivisionDto[] $divisions
     *
     * @return array ['store1_article1' => $totalSubtraction1, 'store2_article2' => $totalSubtraction2, ...]
     */
    public function calculateStockSubtraction(array $divisions): array;

    /**
     * Сгенерировать ключ для поиска вычитаемого количества в self::calculateStockSubtraction().
     */
    public function getSubtractionKey(string $storeId, string $article): string;
}
