<?php

namespace App\Request\Dto\PROVIDER\v2;

final class SetFullImportRemainCounter
{
    /**
     * Количество необработанных аптек.
     */
    private int $unprocessedStoreCount;

    /**
     * Количество отправленных сообщений с остатками.
     */
    private int $sentImportRemainMessageCount;

    public function __construct(int $unprocessedStoreCount, int $sentImportRemainMessageCount)
    {
        $this->unprocessedStoreCount = $unprocessedStoreCount;
        $this->sentImportRemainMessageCount = $sentImportRemainMessageCount;
    }

    public function getUnprocessedStoreCount(): int
    {
        return $this->unprocessedStoreCount;
    }

    public function getSentImportRemainMessageCount(): int
    {
        return $this->sentImportRemainMessageCount;
    }
}
