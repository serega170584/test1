<?php
declare(strict_types=1);

namespace App\Response;

final class SyncOrderStatusSuccessResponse
{
    public function __construct(
        public string $id,
        public string $message,
    ) {
    }
}
