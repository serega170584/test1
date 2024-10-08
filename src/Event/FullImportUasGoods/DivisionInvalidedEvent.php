<?php

declare(strict_types=1);

namespace App\Event\FullImportPROVIDERGoods;

use Symfony\Contracts\EventDispatcher\Event;

class DivisionInvalidedEvent extends Event
{
    public function __construct(
        public string $division
    ) {
    }
}
