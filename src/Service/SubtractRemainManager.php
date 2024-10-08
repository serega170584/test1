<?php

declare(strict_types=1);

namespace App\Service;

use App\Request\Dto\PROVIDER\v1\DivisionDto;
use App\Service\MinimalRemain\MinimalRemainManagerInterface;

class SubtractRemainManager implements SubtractRemainManagerInterface
{
    private MinimalRemainManagerInterface $minimalRemainManager;

    private RemainReserveManager $remainReserveManager;

    public function __construct(
        MinimalRemainManagerInterface $minimalRemainManager,
        RemainReserveManager $remainReserveManager
    ) {
        $this->minimalRemainManager = $minimalRemainManager;
        $this->remainReserveManager = $remainReserveManager;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateStockSubtraction(array $divisions): array
    {
        $stores = array_map(
            static function (DivisionDto $division) {
                return $division->getDivision();
            },
            $divisions
        );
        $articles = $this->getAllArticles($divisions);

        $reserves = $this->remainReserveManager->getStoreReserves($stores);
        $minimalRemains = $this->minimalRemainManager->getMinimalRemains($articles);

        $result = [];

        foreach ($divisions as $division) {
            foreach ($division->getItems() as $item) {
                $reserveKey = $this->remainReserveManager->getReserveKey($division->getDivision(), $item->getCode());
                $reserveQuantity = $reserves[$reserveKey] ?? 0;
                $minimalQuantity = $minimalRemains[$item->getCode()] ?? 0;
                /** количество, которое будет вычитаться из остатка */
                $totalRemainSubtraction = $reserveQuantity + $minimalQuantity;

                $key = $this->getSubtractionKey($division->getDivision(), $item->getCode());
                $result[$key] = $totalRemainSubtraction;
            }
        }

        return $result;
    }

    public function getSubtractionKey(string $storeId, string $article): string
    {
        return $this->remainReserveManager->getReserveKey($storeId, $article);
    }

    /**
     * Получить уникальные артикулы из всех магазинов.
     *
     * @param DivisionDto[] $divisions
     */
    private function getAllArticles(array $divisions): array
    {
        $articles = [];
        foreach ($divisions as $division) {
            foreach ($division->getItems() as $item) {
                $article = $item->getCode();
                $articles[$article] = $article;
            }
        }

        return $articles;
    }
}
