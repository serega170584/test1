<?php
declare(strict_types=1);

namespace App\Tests\Controller\PROVIDER;

use test1\Message\V2\RequestSyncOrderStatuses;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Uid\Uuid;
use const JSON_THROW_ON_ERROR;

class OrdersControllerSyncStatusTest extends WebTestCase
{
    public function testSuccessRequest(): void
    {
        $request = ['Id' => Uuid::v4()->__toString(), 'Orders' => ['000001', '000002', '000003', '100004']];

        $response = $this->sendRequest($request);

        static::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        static::assertNotNull($message = $this->getLastMessageFromQueue());
        static::assertEquals($request['Id'], $message->getRequestId());
        static::assertEquals($request['Orders'], $message->getOrders());
    }

    private function getLastMessageFromQueue(): ?RequestSyncOrderStatuses
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.request_sync_order_statuses');
        $envelops = $transport->getSent();

        return $envelops ? $envelops[0]->getMessage() : null;
    }

    public function testBadRequest(): void
    {
        $response = $this->sendRequest(['Id' => '', 'Orders' => []]);

        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        static::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        static::assertArrayHasKey('errors', $data);
        static::assertArrayHasKey('id', $data['errors']);
        static::assertArrayHasKey('orders', $data['errors']);
    }

    private function sendRequest(mixed $data): Response
    {
        $client = static::createClient();
        $client->request(
            Request::METHOD_POST,
            '/api/PROVIDER/v2/orders/sync-status',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data, JSON_THROW_ON_ERROR)
        );

        return $client->getResponse();
    }
}
