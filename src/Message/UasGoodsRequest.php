<?php

declare(strict_types=1);

namespace App\Message;

use InvalidArgumentException;

class PROVIDERGoodsRequest
{
    public function __construct(
        public readonly array $storeIds,
        public readonly bool $isFullImport = false
    ) {
        if (empty($this->storeIds)) {
            throw new InvalidArgumentException('Empty store ids');
        }
    }
}
