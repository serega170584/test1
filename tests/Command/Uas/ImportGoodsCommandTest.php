<?php

namespace App\Tests\Command\PROVIDER;

use Exception;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ImportGoodsCommandTest extends KernelTestCase
{
    /**
     * @throws Exception
     */
    public function dataProvider(): iterable
    {
        yield [
            'storeIds' => [
                (string) random_int(10000000, 99999999),
                (string) random_int(10000000, 99999999),
                (string) random_int(10000000, 99999999),
                (string) random_int(10000000, 99999999),
                (string) random_int(10000000, 99999999),
                (string) random_int(10000000, 99999999),
            ],
        ];
    }

    /**
     * Тест полного импорта.
     *
     * @dataProvider dataProvider
     *
     * @throws JsonException
     */
    public function testFullImport(array $storeIds): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $this->initMonolithClientMock($storeIds);

        $command = $application->find('app:PROVIDER:import:goods');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.PROVIDER_goods_request');

        self::assertCount(2, $transport->getSent());
    }

    /**
     * @throws JsonException
     */
    private function initMonolithClientMock(array $storeIds): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getContent')
            ->willReturn(json_encode(['stores' => $storeIds], JSON_THROW_ON_ERROR));

        $monolithClientMock = $this->createMock(HttpClientInterface::class);
        $monolithClientMock
            ->method('request')
            ->willReturn($response);

        self::getContainer()->set('test.monolith.PROVIDER.client', $monolithClientMock);
    }
}
