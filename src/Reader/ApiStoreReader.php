<?php

namespace App\Reader;

use App\Client\Monolith\PROVIDER\ApiClient;

/**
 * Получение аптек через API.
 */
class ApiStoreReader implements StoreReaderInterface
{
    private ApiClient $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * {@inheritDoc}
     */
    public function readAll(): array
    {
        return $this->apiClient->requestStores();
    }
}
