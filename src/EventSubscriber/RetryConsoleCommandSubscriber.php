<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Command\RepeatedCommand;
use App\Common\Log\LoggerContextEnum;
use CompileError;
use Flagception\Manager\FeatureManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;
use TypeError;

class RetryConsoleCommandSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly FeatureManagerInterface $featureManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::TERMINATE => 'onTerminate',
            ConsoleEvents::ERROR => 'onError',
        ];
    }

    /**
     * @throws Throwable
     */
    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        if ($error = $this->tryRepeatCommand($event)) {
            throw $error;
        }
    }

    public function onError(ConsoleErrorEvent $event): void
    {
        if ($error = $this->tryRepeatCommand($event, $event->getError())) {
            $event->setError($error);
        }
    }

    /**
     * @param ConsoleErrorEvent|ConsoleTerminateEvent $event
     *
     * @return ?Throwable
     */
    private function tryRepeatCommand($event, Throwable $lastError = null): ?Throwable
    {
        if (!$this->featureManager->isActive('is_enabled_cron_command_retry')) {
            return null;
        }

        $command = $event->getCommand();

        if ($command instanceof RepeatedCommand && !$this->skipCommandRepeat($event)) {
            $maxNumberOfAttempts = $command->maxNumberOfAttempts();
            $attempts = 1;

            while ($event->getExitCode() && $command->commandMustBeRepeated($event->getExitCode(), $lastError)) {
                if ($command->delay($attempts)) {
                    sleep($command->delay($attempts));
                }

                try {
                    if ($attempts >= $maxNumberOfAttempts) {
                        throw new LogicException('Превышено максимальное кол-во попыток повтора команды!', 500, $lastError);
                    }

                    $this->logger->warning(
                        sprintf(
                            'Попытка #%s повторить команду [%s]',
                            $attempts,
                            $event->getInput()->__toString()
                        ),
                        [
                            LoggerContextEnum::EXCEPTION->value => $lastError,
                            LoggerContextEnum::DATA->value => $event->getExitCode(),
                        ]
                    );

                    $event->setExitCode(
                        $command->run($event->getInput(), $event->getOutput())
                    );

                    $lastError = null;
                } catch (Throwable $e) {
                    $lastError = $e;
                    if ($this->skipCommandRepeat($event, $e)) {
                        break;
                    }
                }

                $attempts++;
            }
        }

        return $lastError;
    }

    /**
     * @param ConsoleErrorEvent|ConsoleTerminateEvent $event
     */
    private function skipCommandRepeat($event, Throwable $error = null): bool
    {
        if ($error !== null || $event instanceof ConsoleErrorEvent) {
            $error = $error ?? $event->getError();

            if ($error instanceof CompileError || $error instanceof TypeError || $error instanceof ExceptionInterface) {
                $this->logger->warning(
                    sprintf(
                        'Получена ошибка при которой повторение команды [%s] не возможно',
                        $event->getInput()->__toString()
                    ),
                    [
                        LoggerContextEnum::EXCEPTION->value => $error,
                        LoggerContextEnum::DATA->value => $event->getExitCode(),
                    ]
                );

                return true;
            }
        }

        return false;
    }
}
