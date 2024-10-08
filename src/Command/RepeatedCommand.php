<?php
declare(strict_types=1);

namespace App\Command;

use App\EventSubscriber\RetryConsoleCommandSubscriber;
use Throwable;

/**
 * @see RetryConsoleCommandSubscriber::tryRepeatCommand()
 */
interface RepeatedCommand
{
    public function commandMustBeRepeated(int $extCode, ?Throwable $e = null): bool;

    public function maxNumberOfAttempts(): int;

    public function delay(int $attempts): int;
}
