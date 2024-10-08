<?php

declare(strict_types=1);

namespace App\Service;

use App\Client\Monolith\PROVIDER\ApiClient;
use App\Request\Dto\PROVIDER\v2\SetFullImportRemainCounter;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class MonolithFullImportRemainService
{
    private ApiClient $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function setFullImportCount(int $invalidDivisions, int $sentImportRemainMessageCount): void
    {
        $this->apiClient->setFullImportCount(
            new SetFullImportRemainCounter(
                $invalidDivisions,
                $sentImportRemainMessageCount
            )
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function resetFullImportRemainCount(): void
    {
        $this->apiClient->resetFullImportRemainCount();
    }
}
