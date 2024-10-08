<?php

declare(strict_types=1);

namespace App\Event\FullImportPROVIDERGoods;

use Symfony\Contracts\EventDispatcher\Event;

class StartedEvent extends Event
{
    public function __construct(
        public array $storeIds
    ) {
    }
}
