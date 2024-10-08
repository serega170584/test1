<?php
declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Request\Dto\OrderStatusInterface as ImOrderStatus;
use App\Request\Dto\PROVIDER\OrderStatusInterface as PROVIDEROrderStatus;
use GuzzleHttp\Psr7\Uri;
use test1\Message\V2\ExportOrder;
use test1\Message\V2\ResponseSyncOrderStatuses;
use test1\Message\V2\SyncOrderStatusItem;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use const JSON_THROW_ON_ERROR;

class ResponseSyncOrderStatusesHandlerTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestHandleMessage
     */
    public function testHandleMessage(ResponseSyncOrderStatuses $message, array $PROVIDERStatusesByOrderId): void
    {
        $assertException = null;
        $kernel = self::bootKernel();
        $this->initMockHttpClient(
            static function (string $method, string $url, array $options = [])
            use ($message, $PROVIDERStatusesByOrderId, &$assertException) {

                try {

                    static::assertEquals(Request::METHOD_POST, $method);
                    static::assertEquals('/e-shop/v2/orders/sync-status', (new Uri($url))->getPath());

                    $body = json_decode($options['body'], true, 512, JSON_THROW_ON_ERROR);

                    static::assertEquals($message->getRequestId(), $body['ID']);
                    static::assertIsArray($body['Orders']);

                    foreach ($body['Orders'] as $orderItem) {
                        static::assertEquals($PROVIDERStatusesByOrderId[$orderItem['order']], $orderItem['status']);
                    }

                } catch (AssertionFailedError $e) {
                    $assertException = $e;
                    throw $e;
                }

                return new MockResponse();
            }
        );
        $this->sendMessageToTransport($message);

        $this->executeConsume($kernel);

        if ($assertException) {
            throw $assertException;
        }

    }

    public function dataProviderTestHandleMessage(): iterable
    {
        yield [
            new ResponseSyncOrderStatuses(
                Uuid::v4()->__toString(),
                [
                    new SyncOrderStatusItem('000001', ImOrderStatus::STATUS_CREATED),
                    new SyncOrderStatusItem('000002', 'status-without-mapping'),
                    new SyncOrderStatusItem('000003', null),
                ]
            ),
            [
                '000001' => PROVIDEROrderStatus::STATUS_CREATED,
                '000002' => null,
                '000003' => null,
            ],
        ];
    }

    private function initMockHttpClient(callable $callback): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturnCallback($callback);

        self::getContainer()->set('test.PROVIDER.client', $httpClient);
    }

    private function sendMessageToTransport(ResponseSyncOrderStatuses $message): void
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.response_sync_order_statuses');
        $transport->send(Envelope::wrap($message));
    }

    private function executeConsume(KernelInterface $kernel): void
    {
        $application = new Application($kernel);
        $command = $application->find('messenger:consume');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'receivers' => ['response_sync_order_statuses'],
            '--limit' => '1',
        ]);

        $commandTester->assertCommandIsSuccessful();
    }

}
