<?php

namespace App\Event;

use test1\Message\V2\ExportOrder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Экспорт данных заказа.
 */
class OrderExportEvent extends Event
{
    private ExportOrder $order;

    public function __construct(ExportOrder $order)
    {
        $this->order = $order;
    }

    public function getOrder(): ExportOrder
    {
        return $this->order;
    }
}
