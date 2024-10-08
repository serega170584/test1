<?php
declare(strict_types=1);

namespace App\Tests\Support\Stub;

use App\Client\SnPAdapter\Client;
use Platform\test_corp_adapter\PricesReply;
use Platform\test_corp_adapter\PricesRequest;
use Platform\test_corp_adapter\StocksReply;
use Platform\test_corp_adapter\StocksRequest;

final class StubSnPAdapterClient implements Client
{
    public function pushPrice(PricesRequest $request): PricesReply
    {
        return new PricesReply(['result' => true]);
    }

    public function pushStock(StocksRequest $request): StocksReply
    {
        return new StocksReply(['result' => true]);
    }
}
