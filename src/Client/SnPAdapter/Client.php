<?php
declare(strict_types=1);

namespace App\Client\SnPAdapter;

use Platform\test_corp_adapter\PricesReply;
use Platform\test_corp_adapter\PricesRequest;
use Platform\test_corp_adapter\StocksReply;
use Platform\test_corp_adapter\StocksRequest;

interface Client
{
    /**
     * @throws ClientException
     */
    public function pushPrice(PricesRequest $request): PricesReply;

    /**
     * @throws ClientException
     */
    public function pushStock(StocksRequest $request): StocksReply;
}
