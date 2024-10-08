<?php

declare(strict_types=1);

namespace App\Response\Service\V1;

class MinimalRemainsResponse
{
    /**
     * @var array<MinimalRemainItem>
     */
    private array $items;

    /**
     * @return array<MinimalRemainItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array<MinimalRemainItem> $items
     */
    public function setItems(array $items): MinimalRemainsResponse
    {
        $this->items = $items;

        return $this;
    }
}
