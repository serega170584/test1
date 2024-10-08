<?php
declare(strict_types=1);

namespace App\MessageHandler;

use App\Service\SyncOrderStatusService;
use test1\Message\V2\ResponseSyncOrderStatuses;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Throwable;

class ResponseSyncOrderStatusesHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly SyncOrderStatusService $syncOrderStatusService
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(ResponseSyncOrderStatuses $responseSyncOrderStatuses): void
    {
        $this->syncOrderStatusService->sendOrdersStatusesToPROVIDER($responseSyncOrderStatuses);
    }
}
