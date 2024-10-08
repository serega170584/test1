<?php
declare(strict_types=1);

namespace App\Client\PROVIDER\v2;

final class OrderStatusItem
{
    public function __construct(
        private readonly string $order,
        private readonly ?int $status,
    ) {
    }

    public function getOrder(): string
    {
        return $this->order;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }
}
