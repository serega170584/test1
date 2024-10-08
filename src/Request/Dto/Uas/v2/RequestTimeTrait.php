<?php

namespace App\Request\Dto\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\SerializedName;

trait RequestTimeTrait
{
    /**
     * Время принятия запроса.
     *
     * @SerializedName("Time")
     */
    private string $time;

    public function getTime(): string
    {
        return $this->time;
    }

    public function setTime(string $time): self
    {
        $this->time = $time;

        return $this;
    }
}
