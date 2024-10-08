<?php

namespace App\Tests\MessageHandler;

use App\Message\PROVIDERGoodsRequest;
use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PROVIDERGoodsRequestHandlerTest extends KernelTestCase
{
    /**
     * @throws Exception
     */
    public function receiveMessageProvider(): array
    {
        return [
            [
                (new PROVIDERGoodsRequest([
                    random_int(100000, 999999),
                    random_int(100000, 999999),
                    random_int(100000, 999999),
                ])),
            ],
        ];
    }

    /**
     * @dataProvider receiveMessageProvider
     *
     * @throws Exception
     */
    public function testReceiveMessage(PROVIDERGoodsRequest $message): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $divisions = [];

        foreach ($message->storeIds as $storeId) {
            $divisions[] = [
                'Division' => $storeId,
                'Items' => [
                    [
                        'Code' => (string) random_int(10000000, 99999999),
                        'Quantity' => random_int(1, 10),
                        'Price' => random_int(100, 300),
                    ],
                ],
            ];
        }

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getContent')
            ->willReturn(json_encode($divisions, JSON_THROW_ON_ERROR));

        $PROVIDERApiClient = $this->createMock(HttpClientInterface::class);
        $PROVIDERApiClient
            ->method('request')
            ->willReturn($response);

        self::getContainer()->set('test.PROVIDER.client', $PROVIDERApiClient);

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.PROVIDER_goods_request');
        $transport->send(Envelope::wrap($message));

        $command = $application->find('messenger:consume');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'receivers' => ['PROVIDER_goods_request'],
            '--limit' => '1',
        ]);

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.PROVIDER_goods_process');
        self::assertCount(1, $transport->getSent());
    }
}
