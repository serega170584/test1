<?php
declare(strict_types=1);

namespace App\Tests\Client\SnPAdapter;

use App\Client\SnPAdapter\GrpcClient;
use App\Client\SnPAdapter\ClientException;
use PHPUnit\Framework\Assert;
use Platform\test_corp_adapter\Price;
use Platform\test_corp_adapter\PricesReply;
use Platform\test_corp_adapter\PricesRequest;
use Platform\test_corp_adapter\StocksReply;
use Platform\test_corp_adapter\StocksRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Platform\test_corp_adapter\PusherServiceClient;
use Platform\test_corp_adapter\Source;
use Grpc\UnaryCall;
use Platform\test_corp_adapter\Stock;
use Google\Protobuf\Internal\Message;

final class ClientTest extends KernelTestCase
{

    public function testPushPriceBadResponse(): void
    {
        $this->expectException(ClientException::class);

        $mockPusherServiceClient = $this->createMock(PusherServiceClient::class);
        $mockPusherServiceClient->method('PushPrice')->willReturn($this->createErrorMockUnaryCall());

        $client = new GrpcClient($mockPusherServiceClient);
        $client->pushPrice($this->createFakePricesRequest());
    }

    public function testPushPriceSuccessResponse(): void
    {
        $mockPusherServiceClient = $this->createMock(PusherServiceClient::class);
        $mockPusherServiceClient->method('PushPrice')->willReturn(
            $this->createSuccessMockUnaryCall($reply = new PricesReply(['result' => true]))
        );

        $client = new GrpcClient($mockPusherServiceClient);
        $response = $client->pushPrice($this->createFakePricesRequest());

        Assert::assertEquals($reply->getResult(), $response->getResult());
    }

    private function createFakePricesRequest(): PricesRequest
    {
        $request = new PricesRequest();
        $request->setStoreId('123_PROVIDER');
        $request->setSource(Source::SOURCE_TO);
        $request->setPrices([
            new Price([
                'good_id' => '123123',
                'base_price' => 10020,
            ]),
            new Price([
                'good_id' => '321321',
                'base_price' => 20010,
            ]),
        ]);

        return $request;
    }

    public function testPushStockBadResponse(): void
    {
        $this->expectException(ClientException::class);

        $mockPusherServiceClient = $this->createMock(PusherServiceClient::class);
        $mockPusherServiceClient->method('PushStock')->willReturn($this->createErrorMockUnaryCall());

        $client = new GrpcClient($mockPusherServiceClient);
        $client->pushStock($this->createFakeStocksRequest());
    }

    public function testPushStockSuccessResponse(): void
    {
        $mockPusherServiceClient = $this->createMock(PusherServiceClient::class);
        $mockPusherServiceClient->method('PushStock')->willReturn(
            $this->createSuccessMockUnaryCall($reply = new StocksReply(['result' => true]))
        );

        $client = new GrpcClient($mockPusherServiceClient);
        $response = $client->pushStock($this->createFakeStocksRequest());

        Assert::assertEquals($reply->getResult(), $response->getResult());
    }

    private function createFakeStocksRequest(): StocksRequest
    {
        $request = new StocksRequest();
        $request->setStoreId('123_PROVIDER');
        $request->setSource(Source::SOURCE_TO);
        $request->setStocks([
            new Stock([
                'good_id' => '123123',
                'quantity' => 10,
            ]),
            new Stock([
                'good_id' => '321321',
                'quantity' => 0,
            ]),
        ]);

        return $request;
    }

    private function createSuccessMockUnaryCall(Message $message): UnaryCall
    {
        $mockUnaryCall = $this->createMock(UnaryCall::class);
        $mockUnaryCall->method('wait')->willReturn([
            $message,
            (object)['code' => 0, 'details' => null],
        ]);

        return $mockUnaryCall;
    }

    private function createErrorMockUnaryCall(): UnaryCall
    {

        $mockUnaryCall = $this->createMock(UnaryCall::class);
        $mockUnaryCall->method('wait')->willReturn([
            null,
            (object)['code' => 1, 'details' => 'Some error from grpc'],
        ]);

        return $mockUnaryCall;
    }
}
