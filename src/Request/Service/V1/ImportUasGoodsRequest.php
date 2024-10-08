<?php

declare(strict_types=1);

namespace App\Request\Service\V1;

use App\Request\AutowiredRequestInterface;
use Symfony\Component\Validator\Constraints;

class ImportPROVIDERGoodsRequest implements AutowiredRequestInterface
{
    /**
     * @Constraints\All({
     *     @Constraints\NotBlank(),
     *     @Constraints\Type("numeric")
     * })
     */
    private ?array $storeIds = null;

    /**
     * @return array<int>|null
     */
    public function getStoreIds(): ?array
    {
        return $this->storeIds;
    }

    public function setStoreIds(?array $storeIds): ImportPROVIDERGoodsRequest
    {
        $this->storeIds = $storeIds;

        return $this;
    }
}
