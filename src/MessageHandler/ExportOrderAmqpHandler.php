<?php

namespace App\MessageHandler;

use test1\Message\V2\ExportOrder;

/**
 * //TODO выпилить после переезда на кафку.
 */
class ExportOrderAmqpHandler extends ExportOrderHandler
{
    public function __invoke(ExportOrder $exportOrder)
    {
        if ($this->featureManager->isEnabledMonolithKafka()) {
            return;
        }

        $this->handle($exportOrder);
    }

    public static function getHandledMessages(): iterable
    {
        yield ExportOrder::class => [
            'from_transport' => 'v2_export_orders',
        ];
    }
}
