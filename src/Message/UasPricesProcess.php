<?php

declare(strict_types=1);

namespace App\Message;

use App\Request\Dto\PROVIDER\v1\DivisionDto;

class PROVIDERPricesProcess
{
    /**
     * @param array<DivisionDto> $divisionDtos
     */
    public function __construct(
        public readonly array $divisionDtos
    ) {
    }
}
