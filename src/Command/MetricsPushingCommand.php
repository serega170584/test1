<?php

namespace App\Command;

use Prometheus\CollectorRegistry;
use PrometheusPushGateway\PushGateway;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:metrics:pushing',
    description: 'Send metrics to prometheus push gateway',
)]
class MetricsPushingCommand extends Command
{
    public function __construct(
        private readonly PushGateway $pushGateway,
        private readonly CollectorRegistry $collectorRegistry,
        private readonly string $namespaceCommonLabel
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        while (true) {
            $this->pushGateway->push($this->collectorRegistry, 'prov-adapter-backgrounds', [
                'namespace' => $this->namespaceCommonLabel,
            ]);
            sleep(30);
        }
    }
}
