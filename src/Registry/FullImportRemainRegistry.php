<?php

declare(strict_types=1);

namespace App\Registry;

use App\Command\PROVIDER\ImportGoodsCommand;

/**
 * Хранение данных о процессе полного импорта остатков из УАС.
 *
 * @see ImportGoodsCommand::execute()
 */
class FullImportRemainRegistry
{
    /** Идет процесс полного импорта остатков? */
    private bool $isFullImport = false;

    /** Общее количество импортированных остатков */
    private int $totalImportedRemains = 0;

    /**
     * Список аптек, которые не прошли валидацию.
     *
     * @var string[]
     */
    private array $invalidDivisions = [];

    public function setIsFullImport(bool $isFullImport): void
    {
        $this->isFullImport = $isFullImport;
    }

    public function isFullImport(): bool
    {
        return $this->isFullImport;
    }

    public function addImportRemainCount(int $addedImportRemains): void
    {
        $this->totalImportedRemains += $addedImportRemains;
    }

    public function addInvalidDivision(string $invalidDivision): void
    {
        $this->invalidDivisions[] = $invalidDivision;
    }

    public function getTotalImportedRemains(): int
    {
        return $this->totalImportedRemains;
    }

    public function getInvalidDivisions(): array
    {
        return $this->invalidDivisions;
    }

    public function reset(): self
    {
        $this->totalImportedRemains = 0;
        $this->invalidDivisions = [];
        $this->isFullImport = false;

        return $this;
    }
}
