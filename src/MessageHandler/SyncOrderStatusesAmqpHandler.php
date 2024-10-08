<?php

namespace App\MessageHandler;

use test1\Message\V2\SyncOrderStatuses;

/**
 * TODO выпилить после переезда на кафку.
 */
class SyncOrderStatusesAmqpHandler extends SyncOrderStatusesHandler
{
    public function __invoke(SyncOrderStatuses $orderStatuses)
    {
        if ($this->featureManager->isEnabledMonolithKafka()) {
            return;
        }

        $this->handle($orderStatuses);
    }

    public static function getHandledMessages(): iterable
    {
        yield SyncOrderStatuses::class => [
            'from_transport' => 'v2_sync_order_statuses',
        ];
    }
}
