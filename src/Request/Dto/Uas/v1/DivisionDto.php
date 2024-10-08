<?php

namespace App\Request\Dto\PROVIDER\v1;

/**
 * Данные остатков и цен по аптеке.
 */
class DivisionDto
{
    /**
     * ID аптеки.
     */
    private string $division;

    /**
     * Список остатков.
     *
     * @var DivisionItemDto[]
     */
    private array $items;

    public function getDivision(): string
    {
        return $this->division;
    }

    public function setDivision(string $division): DivisionDto
    {
        $this->division = $division;

        return $this;
    }

    /**
     * @return DivisionItemDto[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param DivisionItemDto[] $items
     */
    public function setItems(array $items): DivisionDto
    {
        $this->items = $items;

        return $this;
    }

    public function addItem(DivisionItemDto $item): DivisionDto
    {
        $this->items[] = $item;

        return $this;
    }
}
