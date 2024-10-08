<?php
declare(strict_types=1);

namespace App\Client\SnPAdapter;

use Platform\test_corp_adapter\PricesReply;
use Platform\test_corp_adapter\PricesRequest;
use Platform\test_corp_adapter\PusherServiceClient;
use Platform\test_corp_adapter\StocksReply;
use Platform\test_corp_adapter\StocksRequest;

final class GrpcClient implements Client
{
    public function __construct(private readonly PusherServiceClient $pusherServiceClient)
    {
    }

    /** {@inheritDoc} */
    public function pushPrice(PricesRequest $request): PricesReply
    {
        /** @var PricesReply $response */
        [$response, $status] = $this->pusherServiceClient->PushPrice($request)->wait();

        if ($status->code) {
            throw new ClientException("Can't push price to snp adapter. [" . $status->code . '] ' . $status->details);
        }

        return $response;
    }

    /** {@inheritDoc} */
    public function pushStock(StocksRequest $request): StocksReply
    {
        /** @var StocksReply $response */
        [$response, $status] = $this->pusherServiceClient->PushStock($request)->wait();

        if ($status->code) {
            throw new ClientException("Can't push stocks to snp adapter. [" . $status->code . '] ' . $status->details);
        }

        return $response;
    }
}
