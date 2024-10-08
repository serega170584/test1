<?php

namespace App\Service;

use test1\Message\V2\ExportOrder;

/**
 * Экспортер заказов.
 */
interface OrderExporterInterface
{
    /**
     * ЭВыполняет экспорт заказа во внешние системы - получатели (УАС).
     */
    public function export(ExportOrder $exportOrder): void;
}
