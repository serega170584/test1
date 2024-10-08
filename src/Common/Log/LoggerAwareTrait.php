<?php

namespace App\Common\Log;

use Throwable;

/**
 * Trait LoggerAwareTrait.
 */
trait LoggerAwareTrait
{
    use \Psr\Log\LoggerAwareTrait;

    public function logError(Throwable $exception, string $level = 'error'): void
    {
        is_callable([$this, $level]) ?: $level = 'error';
        $this->logger->$level(
            sprintf(
                '[%s] %s (%s)',
                get_class($exception),
                $exception->getMessage(),
                $exception->getCode()
            ),
            [
                'error' => $exception,
            ]
        );
    }
}
