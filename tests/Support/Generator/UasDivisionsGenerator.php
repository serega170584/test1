<?php

declare(strict_types=1);

namespace App\Tests\Support\Generator;

use App\Request\Dto\PROVIDER\v1\DivisionDto;
use App\Request\Dto\PROVIDER\v1\DivisionItemDto;
use Exception;

class PROVIDERDivisionsGenerator
{
    /**
     * @throws Exception
     */
    public function generate(int $countDivisions, int $countDivisionItems): array
    {
        $divisions = [];
        for ($i = 0; $i < $countDivisions; $i++) {
            $items = [];
            for ($j = 0; $j < $countDivisionItems; $j++) {
                $items[] =
                    (new DivisionItemDto())
                        ->setCode((string) random_int(10000000, 99999999))
                        ->setPrice(random_int(100, 1000))
                        ->setQuantity(random_int(1, 10));
            }
            $divisions[] =
                (new DivisionDto())
                    ->setDivision((string) random_int(10000000, 99999999))
                    ->setItems($items);
        }

        return $divisions;
    }
}
