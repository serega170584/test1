<?php

namespace App\Client\PROVIDER\v2;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Информармация о товаре.
 */
class OrderProductDto
{
    /**
     * Артикул.
     *
     * @SerializedName("Code")
     */
    private string $code;

    /**
     * Код МФ.
     *
     * @SerializedName("VendorCode")
     */
    private string $vendorCode;

    /**
     * Штрих-код.
     *
     * @SerializedName("Barcode")
     */
    private string $barcode;

    public function __construct(string $code, string $vendorCode, string $barcode)
    {
        $this->code = $code;
        $this->vendorCode = $vendorCode;
        $this->barcode = $barcode;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getVendorCode(): string
    {
        return $this->vendorCode;
    }

    public function getBarcode(): string
    {
        return $this->barcode;
    }
}
