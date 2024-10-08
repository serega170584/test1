<?php
declare(strict_types=1);

namespace App\Command\PROVIDER;

use App\Client\PROVIDER\ApiClient;
use App\Command\AbstractCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Выполняет переключение активной тестовой зоны УАС
 */
class ActivatePROVIDERTestZone extends AbstractCommand
{
    private const NAME = 'app:PROVIDER:test:set-active-zone';
    private const ARG_ZONE_NAME = 'zone';

    private const AVAILABLE_ZONES = [
        'stage', 'stage2', 'test', 'test2',
    ];

    private ApiClient $apiClient;

    public function __construct(ApiClient $apiClient, LoggerInterface $logger)
    {
        $this->apiClient = $apiClient;
        parent::__construct($logger);
    }

    public function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Call PROVIDER to set active test zone')
            ->addArgument(
                self::ARG_ZONE_NAME,
                InputArgument::REQUIRED,
                'PROVIDER test zone name to be activated'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $zone = $input->getArgument(self::ARG_ZONE_NAME);

        if (!in_array($zone, self::AVAILABLE_ZONES)) {
            throw new \UnexpectedValueException("Unexpected zone name $zone");
        }

        $this->getLogger()->info('setting new test zone to PROVIDER', ['zone' => $zone]);
        $this->apiClient->setActiveTestZone($zone);
        $this->getLogger()->info('done', ['zone' => $zone]);

        return 0;
    }
}
