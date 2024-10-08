<?php

namespace App\Client\PROVIDER;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Данные для запроса полных остатков в УАСе.
 */
class DivisionsItemsDto
{
    /**
     * @SerializedName("MinExpiryDays")
     */
    private int $minExpiryDays;

    /**
     * Список ID аптек.
     *
     * @var string[]
     *
     * @SerializedName("Divisions")
     */
    private array $divisions;

    public function __construct(array $divisions, int $minExpiryDays = 90)
    {
        $this->divisions = $divisions;
        $this->minExpiryDays = $minExpiryDays;
    }

    public function getMinExpiryDays(): int
    {
        return $this->minExpiryDays;
    }

    /**
     * @return string[]
     */
    public function getDivisions(): array
    {
        return $this->divisions;
    }
}
