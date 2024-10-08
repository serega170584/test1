<?php
declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\RetryConsoleCommandSubscriber;
use App\Tests\Support\Stub\StubRepeatCommand;
use Flagception\Manager\FeatureManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

class RetryConsoleCommandSubscriberTest extends KernelTestCase
{
    private const SUCCESS_EXIT_CODE = 0;

    private const FAILURE_EXIT_CODE = 1;

    protected function setUp(): void
    {
        $this->consoleSubscriber = new RetryConsoleCommandSubscriber(
            $this->createConfiguredMock(
                FeatureManagerInterface::class,
                [
                    'isActive' => true,
                ]
            ),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testOnTerminateMaxAttempts(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Превышено максимальное кол-во попыток повтора команды!');

        $command = new StubRepeatCommand(self::FAILURE_EXIT_CODE);

        $this->consoleSubscriber->onTerminate(
            new ConsoleTerminateEvent(
                $command,
                new ArrayInput([]),
                new StreamOutput(fopen('php://memory', 'wb', false)),
                self::FAILURE_EXIT_CODE
            )
        );
    }

    public function testOnErrorMaxAttempts(): void
    {
        $command = new StubRepeatCommand(self::FAILURE_EXIT_CODE);
        $event = new ConsoleErrorEvent(
            new ArrayInput([]),
            new StreamOutput(fopen('php://memory', 'wb', false)),
            new \RuntimeException('Test error'),
            $command
        );

        $this->consoleSubscriber->onError($event);

        $this->assertEquals(
            LogicException::class,
            get_class($event->getError())
        );

        $this->assertEquals(
            'Превышено максимальное кол-во попыток повтора команды!',
            $event->getError()->getMessage()
        );
    }

    public function testSuccessRetryOnError(): void
    {
        $attempts = 0;
        $command = $this->createMock(StubRepeatCommand::class);

        $command
            ->method('maxNumberOfAttempts')
            ->willReturn(3);

        $command
            ->method('delay')
            ->willReturn(0);

        $command->method('commandMustBeRepeated')
            ->willReturnCallback(static function (int $extCode, ?\Throwable $e = null) {
                return $extCode || $e;
            })
        ;

        $command
            ->method('run')
            ->willReturnCallback(static function () use (&$attempts) {
                if ($attempts) {
                    return self::SUCCESS_EXIT_CODE;
                }

                ++$attempts;
                throw new \RuntimeException('Test error then try repeat command');
            });

        $event = new ConsoleErrorEvent(
            new ArrayInput([]),
            new StreamOutput(fopen('php://memory', 'wb', false)),
            new \RuntimeException('Test error'),
            $command
        );

        $this->consoleSubscriber->onError($event);

        $this->assertEquals(
            self::SUCCESS_EXIT_CODE,
            $event->getExitCode()
        );
    }
}