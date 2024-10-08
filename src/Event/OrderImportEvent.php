<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Импорт данных заказа.
 */
class OrderImportEvent extends Event
{
    /**
     * Номер заказа.
     */
    private string $number;

    /**
     * Статус в УАС.
     */
    private int $PROVIDERStatus;

    /**
     * Тип заказа.
     */
    private int $type;

    public function __construct(string $number, int $PROVIDERStatus, int $type)
    {
        $this->number = $number;
        $this->PROVIDERStatus = $PROVIDERStatus;
        $this->type = $type;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getPROVIDERStatus(): int
    {
        return $this->PROVIDERStatus;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
