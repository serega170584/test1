<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\PROVIDERGoodsRequest;
use App\Service\ImportPROVIDERGoodsService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class PROVIDERGoodsRequestHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ImportPROVIDERGoodsService $importPROVIDERGoodsService
    ) {
    }

    public function __invoke(PROVIDERGoodsRequest $message): void
    {
        $this->importPROVIDERGoodsService->requestPROVIDERGoods($message->storeIds, $message->isFullImport);
    }
}
