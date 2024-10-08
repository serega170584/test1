<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\PROVIDERGoodsProcess;
use App\Service\ImportPROVIDERGoodsService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class PROVIDERGoodsProcessHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ImportPROVIDERGoodsService $importPROVIDERGoodsService
    ) {
    }

    public function __invoke(PROVIDERGoodsProcess $message): void
    {
        $this->importPROVIDERGoodsService->processPROVIDERGoods($message->divisionDtos, $message->isFullImport);
    }
}
