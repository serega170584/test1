<?php

namespace App\Command;

use App\Metric\MetricsCollector\DatabaseQueuesCountMessagesMetricsCollector;
use App\Metric\MetricsCollector\FullImportPROVIDERGoodsMetricsCollector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:metrics:collecting',
    description: 'Collecting metrics',
)]
class MetricsCollectingCommand extends Command
{
    public function __construct(
        private readonly DatabaseQueuesCountMessagesMetricsCollector $databaseQueuesCountMessagesMetricsCollector,
        private readonly FullImportPROVIDERGoodsMetricsCollector $fullImportPROVIDERGoodsMetricsCollector,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        while (true) {
            $this->databaseQueuesCountMessagesMetricsCollector->collect();
            $this->fullImportPROVIDERGoodsMetricsCollector->collect();

            sleep(30);
        }
    }
}
