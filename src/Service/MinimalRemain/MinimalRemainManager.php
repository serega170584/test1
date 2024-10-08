<?php

declare(strict_types=1);

namespace App\Service\MinimalRemain;

use App\Dto\SaveMinimalRemainDto;
use App\Entity\MinimalRemain;
use App\Exception\InvalidMinimalRemainQuantityException;
use App\Repository\MinimalRemainRepositoryInterface;

class MinimalRemainManager implements MinimalRemainManagerInterface
{
    private MinimalRemainRepositoryInterface $minimalRemainRepository;
    /** Включен ли функционал минимального остатка */
    private bool $isMinimalStockEnabled;

    public function __construct(
        MinimalRemainRepositoryInterface $minimalRemainRepository,
        bool $isMinimalStockEnabled,
    ) {
        $this->minimalRemainRepository = $minimalRemainRepository;
        $this->isMinimalStockEnabled = $isMinimalStockEnabled;
    }

    public function getMinimalRemains(array $articles): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $minimalStocks = $this->minimalRemainRepository->findAllByArticles($articles);

        $result = [];
        foreach ($minimalStocks as $minimalStock) {
            $result[$minimalStock->getArticle()] = $minimalStock->getMinimalRemainQuantity();
        }

        return $result;
    }

    /**
     * @throws InvalidMinimalRemainQuantityException
     */
    public function saveMinimalRemain(SaveMinimalRemainDto $minimalRemainDto): MinimalRemain
    {
        if ($minimalRemainDto->getMinimalRemainQuantity() <= 0) {
            throw new InvalidMinimalRemainQuantityException('Минимальный остаток должен быть больше 0');
        }

        $article = $minimalRemainDto->getArticle();

        $minimalRemain = $this->minimalRemainRepository->findOneBy([
            'article' => $article,
        ]);
        if ($minimalRemain) {
            $minimalRemain->setMinimalRemainQuantity($minimalRemainDto->getMinimalRemainQuantity());
        } else {
            $minimalRemain = (new MinimalRemain())
                ->setArticle($minimalRemainDto->getArticle())
                ->setMinimalRemainQuantity($minimalRemainDto->getMinimalRemainQuantity());
        }

        return $this->minimalRemainRepository->save($minimalRemain);
    }

    public function deleteByArticles(array $articles): void
    {
        $this->minimalRemainRepository->deleteByArticles($articles);
    }

    private function isEnabled(): bool
    {
        return $this->isMinimalStockEnabled;
    }
}
