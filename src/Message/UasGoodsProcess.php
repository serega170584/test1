<?php

declare(strict_types=1);

namespace App\Message;

use App\Request\Dto\PROVIDER\v1\DivisionDto;

class PROVIDERGoodsProcess
{
    /**
     * @param array<DivisionDto> $divisionDtos
     * @param bool $isFullImport
     */
    public function __construct(
        public readonly array $divisionDtos,
        public readonly bool $isFullImport = false
    ) {
    }
}
