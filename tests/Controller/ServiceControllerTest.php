<?php

namespace App\Tests\Controller;

use App\Controller\Api\Service\V1\ServiceController;
use App\Entity\MinimalRemain;
use App\Manager\FeatureManager;
use App\Message\PROVIDERGoodsRequest;
use App\Repository\MinimalRemainRepository;
use App\Tests\Dto\MinimalRemainDto;
use Exception;
use Flagception\Manager\FeatureManagerInterface;
use test1\Message\V2\SyncOrderStatuses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\SentToFailureTransportStamp;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ServiceControllerTest extends WebTestCase
{
    private const URL_PROCESS_FAILED_MESSAGES = '/api/service/v1/process-failed-messages';

    private const URL_MINIMAL_REMAINS = '/api/service/v1/minimal-remains';

    private const URL_IMPORT_PROVIDER_GOODS = '/api/service/v1/import-PROVIDER-goods';

    private const URL_EXPORT_UPDATED_ORDER = '/api/service/v1/export-updated-order';

    public function createClientWithAuth(): KernelBrowser
    {
        return self::createClient([], [
            'PHP_AUTH_USER' => 'test',
            'PHP_AUTH_PW' => 'pass',
        ]);
    }

    private function initMockFeatureManager(bool $isEnabledMonolithKafka): void
    {
        $featureManager = $this->createMock(FeatureManagerInterface::class);
        $featureManager
            ->method('isActive')
            ->with(FeatureManager::IS_ENABLED_MONOLITH_KAFKA)
            ->willReturn($isEnabledMonolithKafka);

        self::getContainer()->set('flagception.manager.feature_manager', $featureManager);
    }

    private function getMinimalRemainRepository(): MinimalRemainRepository
    {
        return self::getContainer()
                   ->get('doctrine')
                   ->getRepository(MinimalRemain::class);
    }

    public function authFailedProvider(): array
    {
        return [
            ['POST', self::URL_MINIMAL_REMAINS],
            ['DELETE', self::URL_MINIMAL_REMAINS],
            ['GET', self::URL_MINIMAL_REMAINS],
            ['POST', self::URL_PROCESS_FAILED_MESSAGES],
            ['PUT', self::URL_IMPORT_PROVIDER_GOODS],
        ];
    }

    /**
     * @throws Exception
     */
    public function syncOrderStatusMessageProvider(): array
    {
        $message = new SyncOrderStatuses(
            [
                (string) random_int(100000, 999999),
                (string) random_int(100000, 999999),
                (string) random_int(100000, 999999),
                (string) random_int(100000, 999999),
            ]
        );
        $message->setRequestId(Uuid::v4());

        return [
            'is_disabled_kafka' => [$message, false, 'v2_sync_order_statuses'],
            'is_enabled_kafka' => [$message, true, 'orders_sync_statuses_kafka'],
        ];
    }

    /**
     * @throws Exception
     */
    public function minimalRemainProvider(): array
    {
        return [
            'default' => [
                (new MinimalRemainDto())
                    ->setArticle((string) random_int(10000000, 99999999))
                    ->setMinimalRemainQuantity(random_int(1, 10)),
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function badRequestsSaveMinimalRemainsProvider(): array
    {
        return [
            'empty_string_article' => [['article' => '', 'minimalRemainQuantity' => random_int(1, 10)]],
            'not_string_article' => [['article' => [123], 'minimalRemainQuantity' => random_int(1, 10)]],
            'without_article' => [['minimalRemainQuantity' => random_int(1, 10)]],
            'zero_minimal_remain_quantity' => [['article' => (string) random_int(10000000, 99999999), 'minimalRemainQuantity' => 0]],
            'not_numeric_minimal_remain_quantity' => [['article' => (string) random_int(10000000, 99999999), 'minimalRemainQuantity' => 'str']],
            'negative_minimal_remain_quantity' => [['article' => (string) random_int(10000000, 99999999), 'minimalRemainQuantity' => random_int(-10, -1)]],
            'without_minimal_remain_quantity' => [['article' => (string) random_int(10000000, 99999999)]],
        ];
    }

    public function badRequestsDeleteMinimalRemainsProvider(): array
    {
        return [
            'empty_articles' => [['articles' => []]],
            'without_articles' => [[]],
            'not_string_articles' => [['articles' => [[]]]],
        ];
    }

    /**
     * @throws Exception
     */
    public function importPROVIDERGoodsProvider(): array
    {
        return [
            'default' => [
                [
                    random_int(100000, 999999),
                    random_int(100000, 999999),
                    random_int(100000, 999999),
                ],
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function badRequestsImportPROVIDERGoodsProvider(): array
    {
        return [
            'not_array_store_ids' => [['storeIds' => (string) random_int(100000, 999999)]],
            'not_int_store_ids' => [['storeIds' => ['str']]],
        ];
    }

    /**
     * @dataProvider authFailedProvider
     */
    public function testAuthFailed(string $method, string $url): void
    {
        $client = self::createClient();

        $client->jsonRequest($method, $url);

        self::assertResponseStatusCodeSame(401);
    }

    public function testEmptyFailedMessagesList(): void
    {
        $client = $this->createClientWithAuth();

        $client->jsonRequest('POST', self::URL_PROCESS_FAILED_MESSAGES);

        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider syncOrderStatusMessageProvider
     *
     * @throws Exception
     */
    public function testUnprocessableFailedMessages(SyncOrderStatuses $message, bool $isEnabledMonolithKafka, string $originalTransport): void
    {
        $client = $this->createClientWithAuth();
        /** @var InMemoryTransport $transportFailed */
        $transportFailed = self::getContainer()->get('messenger.transport.failed');
        $transportFailed->send(
            Envelope::wrap($message, [
                new SentToFailureTransportStamp($originalTransport),
            ])
        );

        $this->initMockFeatureManager($isEnabledMonolithKafka);

        $client->jsonRequest('POST', self::URL_PROCESS_FAILED_MESSAGES, ['limit' => count($transportFailed->getSent()) + 1]);

        self::assertResponseIsUnprocessable();
        self::assertEmpty($transportFailed->getAcknowledged());
    }

    /**
     * @dataProvider syncOrderStatusMessageProvider
     *
     * @throws Exception
     */
    public function testProcessFailedMessages(SyncOrderStatuses $message, bool $isEnabledMonolithKafka, string $originalTransport): void
    {
        $client = $this->createClientWithAuth();

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getContent')
            ->willReturn(json_encode(['Id' => Uuid::v4(), 'Time' => date('Y-m-d H:i:s')], JSON_THROW_ON_ERROR));
        $PROVIDERClient = $this->createMock(HttpClientInterface::class);
        $PROVIDERClient
            ->expects(self::once())
            ->method('request')
            ->willReturn($response);
        self::getContainer()->set('test.PROVIDER.client', $PROVIDERClient);

        /** @var InMemoryTransport $transportFailed */
        $transportFailed = self::getContainer()->get('messenger.transport.failed');
        $transportFailed->send(
            Envelope::wrap($message, [
                new SentToFailureTransportStamp($originalTransport),
            ])
        );

        $this->initMockFeatureManager($isEnabledMonolithKafka);

        $client->jsonRequest('POST', self::URL_PROCESS_FAILED_MESSAGES);

        self::assertResponseIsSuccessful();
        self::assertCount(count($transportFailed->getSent()), $transportFailed->getAcknowledged());
    }

    /**
     * @dataProvider minimalRemainProvider
     */
    public function testGetMinimalRemains(MinimalRemainDto $dto): void
    {
        $client = $this->createClientWithAuth();

        $repo = $this->getMinimalRemainRepository();
        $minimalRemain = (new MinimalRemain())
            ->setArticle($dto->getArticle())
            ->setMinimalRemainQuantity($dto->getMinimalRemainQuantity());
        $repo->save($minimalRemain);

        $client->jsonRequest('GET', self::URL_MINIMAL_REMAINS);

        $responseContent = $client->getResponse()->getContent();

        self::assertStringContainsString('"article":"' . $dto->getArticle() . '"', $responseContent);
        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider minimalRemainProvider
     *
     * @throws Exception
     */
    public function testSaveMinimalRemains(MinimalRemainDto $dto): void
    {
        $client = $this->createClientWithAuth();
        $repo = $this->getMinimalRemainRepository();

        $client->jsonRequest('POST', self::URL_MINIMAL_REMAINS, [
            'article' => $dto->getArticle(),
            'minimalRemainQuantity' => $dto->getMinimalRemainQuantity(),
        ]);

        /** @var MinimalRemain $minimalRemain */
        $minimalRemain = $repo->findOneBy([
            'article' => $dto->getArticle(),
        ]);

        self::assertEquals($dto->getMinimalRemainQuantity(), $minimalRemain?->getMinimalRemainQuantity());
        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider badRequestsSaveMinimalRemainsProvider
     */
    public function testBadRequestsSaveMinimalRemains(array $body): void
    {
        $client = $this->createClientWithAuth();

        $client->jsonRequest('POST', self::URL_MINIMAL_REMAINS, $body);

        self::assertResponseStatusCodeSame(400);
    }

    /**
     * @dataProvider minimalRemainProvider
     */
    public function testDeleteMinimalRemains(MinimalRemainDto $dto): void
    {
        $client = $this->createClientWithAuth();
        $repo = $this->getMinimalRemainRepository();

        $minimalRemain = (new MinimalRemain())
            ->setArticle($dto->getArticle())
            ->setMinimalRemainQuantity($dto->getMinimalRemainQuantity());
        $repo->save($minimalRemain);

        $minimalRemain = $repo->findOneBy(['article' => $dto->getArticle()]);

        self::assertNotEmpty($minimalRemain);

        $client->jsonRequest('DELETE', self::URL_MINIMAL_REMAINS, [
            'articles' => [$dto->getArticle()],
        ]);

        $minimalRemain = $repo->findOneBy(['article' => $dto->getArticle()]);

        self::assertEmpty($minimalRemain);
        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider badRequestsDeleteMinimalRemainsProvider
     */
    public function testBadRequestsDeleteMinimalRemains(array $body): void
    {
        $client = $this->createClientWithAuth();

        $client->jsonRequest('DELETE', self::URL_MINIMAL_REMAINS, $body);

        self::assertResponseStatusCodeSame(400);
    }

    /**
     * @dataProvider importPROVIDERGoodsProvider
     *
     * @throws Exception
     */
    public function testImportPROVIDERGoods(?array $storeIds): void
    {
        $client = $this->createClientWithAuth();

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.PROVIDER_goods_request');

        $body = [];
        if ($storeIds !== null) {
            $body['storeIds'] = $storeIds;
        }

        $client->jsonRequest('PUT', self::URL_IMPORT_PROVIDER_GOODS, $body);

        $expectedMessage = (new PROVIDERGoodsRequest($storeIds));
        $sentMessages = $transport->getSent();

        if ($sentMessages) {
            $sentMessage = $sentMessages[0]->getMessage();
            self::assertEquals($sentMessage, $expectedMessage);
        }

        self::assertResponseIsSuccessful();
        self::assertNotEmpty($sentMessages);
    }

    /**
     * @dataProvider badRequestsImportPROVIDERGoodsProvider
     */
    public function testBadRequestsImportPROVIDERGoods(array $body): void
    {
        $client = $this->createClientWithAuth();

        $client->jsonRequest('PUT', self::URL_IMPORT_PROVIDER_GOODS, $body);

        self::assertResponseStatusCodeSame(400);
    }

    /**
     * @see ServiceController::importUpdatedOrderStatus()
     */
    public function testSuccessfulExportUpdatedOrder(): void
    {
        $client = $this->createClientWithAuth();

        $this->initSuccessMockPROVIDERHttpClient();

        $body = [
            'number' => 123,
            'status' => 'L',
            'type' => 2,
            'accept_code' => "AAA",
            'requestId' => "abc"
        ];

        $client->jsonRequest('PUT', self::URL_EXPORT_UPDATED_ORDER, $body);

        self::assertResponseStatusCodeSame(200);
    }

    /**
     * @see ServiceController::importUpdatedOrderStatus()
     */
    public function testBadExportUpdatedOrder(): void
    {
        $client = $this->createClientWithAuth();

        $this->initBadMockPROVIDERHttpClient();

        $body = [
            'number' => 123,
            'status' => 'L',
            'type' => 2,
            'accept_code' => "AAA",
            'requestId' => "abc"
        ];

        $client->jsonRequest('PUT', self::URL_EXPORT_UPDATED_ORDER, $body);

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @see ServiceController::importUpdatedOrderStatus()
     */
    public function testBad500ExportUpdatedOrder(): void
    {
        $client = $this->createClientWithAuth();

        $this->initBad500MockPROVIDERHttpClient();

        $body = [
            'number' => 123,
            'status' => 'L',
            'type' => 2,
            'accept_code' => "AAA",
            'requestId' => "abc"
        ];

        $client->jsonRequest('PUT', self::URL_EXPORT_UPDATED_ORDER, $body);

        self::assertResponseStatusCodeSame(500);
    }

    private function initSuccessMockPROVIDERHttpClient(): void
    {
        $PROVIDERClient = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getContent')
            ->willReturn('{}');

        $PROVIDERClient
            ->expects(self::once())
            ->method('request')
            ->willReturn($response);

        self::getContainer()->set('test.PROVIDER.client', $PROVIDERClient);
    }

    private function initBadMockPROVIDERHttpClient(): void
    {
        $PROVIDERClient = $this->createMock(HttpClientInterface::class);
        $exception = $this->createMock(HttpExceptionInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock
            ->method('getStatusCode')
            ->willReturn(404);
        $exception
            ->method('getResponse')
            ->willReturn($responseMock);

        $PROVIDERClient
            ->expects(self::once())
            ->method('request')
            ->willThrowException($exception);

        self::getContainer()->set('test.PROVIDER.client', $PROVIDERClient);
    }

    private function initBad500MockPROVIDERHttpClient(): void
    {
        $PROVIDERClient = $this->createMock(HttpClientInterface::class);
        $exception = $this->createMock(HttpExceptionInterface::class);

        $PROVIDERClient
            ->expects(self::once())
            ->method('request')
            ->willThrowException(new Exception('Error'));

        self::getContainer()->set('test.PROVIDER.client', $PROVIDERClient);
    }
}
