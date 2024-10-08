<?php
declare(strict_types=1);

namespace App\Tests\Support\Stub;

use App\Command\RepeatedCommand;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StubRepeatCommand extends Command implements RepeatedCommand
{
    /**
     * @var int
     */
    private $exitCode;

    /**
     * @var Exception
     */
    private $exception;

    public function __construct(int $exitCode, Exception $exception = null)
    {
        $this->exitCode = $exitCode;
        $this->exception = $exception;

        parent::__construct('test:stub:repeat:command');
    }

    public function setExitCode(int $exitCode): self
    {
        $this->exitCode = $exitCode;

        return $this;
    }

    public function setException(?Exception $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->exception) {
            throw $this->exception;
        }

        return $this->exitCode;
    }

    public function commandMustBeRepeated(int $extCode, ?\Throwable $e = null): bool
    {
        return $extCode || $e;
    }

    public function delay(int $attempts): int
    {
        return 0;
    }

    public function maxNumberOfAttempts(): int
    {
        return 3;
    }
}