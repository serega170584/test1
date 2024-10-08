<?php

namespace App\Reader;

/**
 * Интерфейс для читателей аптек.
 */
interface StoreReaderInterface
{
    /**
     * Возвращает весь список ID аптек.
     */
    public function readAll(): array;
}
