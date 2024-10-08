<?php

declare(strict_types=1);

namespace App\Metric\MetricsCollector;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Prometheus\Exception\MetricsRegistrationException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;

class ConsoleMetricsCollector implements MetricsCollectorInterface
{
    use MetricsCollectorInitTrait;

    private const EXCLUDED_COMMAND_NAMES = [
        'messenger:consume',
        'app:metrics:pushing',
        'app:metrics:collecting',
    ];

    private array $startsStack = [];

    public function start(): void
    {
        $this->startsStack[] = microtime(true);
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function stop(Command $command, int $exitCode): void
    {
        $currentStart = array_pop($this->startsStack);
        if (!$currentStart) {
            throw new RuntimeException('Not started timer');
        }

        $commandName = $command->getName();

        if (!$commandName || in_array($commandName, self::EXCLUDED_COMMAND_NAMES, true)) {
            return;
        }

        $labelsNames = [
            'command_name',
            'exit_code',
        ];
        $labels = [
            $commandName,
            $exitCode,
        ];

        $timeDiff = round(microtime(true) - $currentStart, 3);

        $metric = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'console_commands_handling_time',
            'Console commands handling time, in sec.',
            $labelsNames
        );
        $metric->set($timeDiff, $labels);

        $metric = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'console_commands_allocated_memory',
            'Allocated memory for console command processing, in bytes',
            $labelsNames
        );
        $metric->set(memory_get_peak_usage(), $labels);

        $metric = $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'console_commands_count',
            'Count of console command',
            $labelsNames
        );
        $metric->inc($labels);
    }
}
