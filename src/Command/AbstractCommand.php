<?php

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Базовый класс команд.
 */
abstract class AbstractCommand extends Command
{
    private LoggerInterface $logger;

    private Stopwatch $watch;

    private SymfonyStyle $io;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->watch = new Stopwatch();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->watch->start($this->getName());
        $this->io = new SymfonyStyle($input, $output);
    }

    public function getStopWatch(): Stopwatch
    {
        return $this->watch;
    }

    public function getIo(): SymfonyStyle
    {
        return $this->io;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
