<?php
declare(strict_types=1);

namespace App\Command;

use Throwable;

trait DefaultImplementationForRepeatedCommandTrait
{
    public function commandMustBeRepeated(int $extCode, ?Throwable $e = null): bool
    {
        return $extCode || $e;
    }

    public function maxNumberOfAttempts(): int
    {
        return 3;
    }

    public function delay(int $attempts): int
    {
        return $attempts * 60;
    }
}
