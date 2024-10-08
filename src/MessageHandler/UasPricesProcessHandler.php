<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\PROVIDERPricesProcess;
use App\Service\ImportPROVIDERGoodsService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class PROVIDERPricesProcessHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ImportPROVIDERGoodsService $importPROVIDERGoodsService
    ) {
    }

    public function __invoke(PROVIDERPricesProcess $message): void
    {
        $this->importPROVIDERGoodsService->processPROVIDERPrices($message->divisionDtos);
    }
}
