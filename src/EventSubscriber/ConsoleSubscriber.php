<?php

namespace App\EventSubscriber;

use App\Command\AbstractCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Обработка консольных событий.
 */
class ConsoleSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onBeforeRunning',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
        ];
    }

    public function onBeforeRunning(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();

        $io = new SymfonyStyle($event->getInput(), $event->getOutput());

        $io->newLine();
        $io->writeln(sprintf('> Running command <info>%s</info>', $command->getName()));
        $io->section($command->getDescription());
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();

        // Только команды приложения
        /** @var Stopwatch $stopwatch */
        if ($command instanceof AbstractCommand
            && ($stopwatch = $command->getStopWatch())
            && $stopwatch->isStarted($command->getName())
        ) {
            $io = $command->getIo();

            $stopwatchEvent = $stopwatch->stop($command->getName());

            $io->writeln(
                sprintf('<info>Execution time:</info> <comment>%.2f ms</comment>', $stopwatchEvent->getDuration())
            );
            $io->writeln(
                sprintf(
                    '<info>Usage memory:</info> <comment>%.2f MB</comment>',
                    $stopwatchEvent->getMemory() / (1024 ** 2)
                )
            );
            $io->newLine();
        }
    }
}
