<?php

namespace App\Request\Dto\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\SerializedName;

trait RequestIdTrait
{
    /**
     * ID запроса.
     *
     * @SerializedName("Id")
     */
    private string $id;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }
}
