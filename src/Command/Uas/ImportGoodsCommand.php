<?php

namespace App\Command\PROVIDER;

use App\Command\AbstractCommand;
use App\Service\ImportPROVIDERGoodsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Импорт полных остатков.
 */
class ImportGoodsCommand extends AbstractCommand
{
    public const NAME = 'app:PROVIDER:import:goods';

    // Список аптек (ручное обновление)
    private const OPTION_STORE = 'store-id';

    private ImportPROVIDERGoodsService $importPROVIDERGoodsService;

    public function __construct(
        LoggerInterface $logger,
        ImportPROVIDERGoodsService $importPROVIDERGoodsService
    ) {
        parent::__construct($logger);
        $this->importPROVIDERGoodsService = $importPROVIDERGoodsService;
    }

    public function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Full import of prices and remains from PROVIDER')
            ->addOption(
                self::OPTION_STORE,
                's',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Store ids'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Список ID аптек
        $storeIds = $input->getOption(self::OPTION_STORE);

        $this->importPROVIDERGoodsService->dispatchPROVIDERGoodsRequests($storeIds);

        return self::SUCCESS;
    }
}
