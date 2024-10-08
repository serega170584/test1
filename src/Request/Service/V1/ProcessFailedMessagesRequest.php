<?php

declare(strict_types=1);

namespace App\Request\Service\V1;

use App\Request\AutowiredRequestInterface;
use Symfony\Component\Validator\Constraints;

class ProcessFailedMessagesRequest implements AutowiredRequestInterface
{
    /**
     * @Constraints\Positive
     */
    private ?int $limit = null;

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): ProcessFailedMessagesRequest
    {
        $this->limit = $limit;

        return $this;
    }
}
