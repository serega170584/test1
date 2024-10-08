<?php

declare(strict_types=1);

namespace App\Service\MinimalRemain;

use App\Dto\SaveMinimalRemainDto;
use App\Entity\MinimalRemain;

interface MinimalRemainManagerInterface
{
    /**
     * Получение минимальных остатков для товара.
     * Пояснение: по законодательству в аптеке всегда должен быть в наличии определенный перечень
     * товаров, которые не разрешается продавать.
     */
    public function getMinimalRemains(array $articles);

    public function saveMinimalRemain(SaveMinimalRemainDto $minimalRemainDto): MinimalRemain;

    public function deleteByArticles(array $articles): void;
}
