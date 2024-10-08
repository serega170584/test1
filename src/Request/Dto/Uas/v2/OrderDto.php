<?php

namespace App\Request\Dto\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Заказ.
 */
class OrderDto extends \App\Request\Dto\PROVIDER\v1\OrderDto
{
    use RequestIdTrait;

    /**
     * Уникальный идентификатор аптеки в государственной системе маркировки.
     *
     * @SerializedName("Sid")
     */
    private ?string $sid = null;

    public function setSid(?string $sid): self
    {
        $this->sid = $sid;

        return $this;
    }

    public function getSid(): ?string
    {
        return $this->sid;
    }
}
