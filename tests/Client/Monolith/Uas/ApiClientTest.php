<?php

namespace App\Tests\Client\Monolith\PROVIDER;

use App\Client\Monolith\PROVIDER\ApiClient;
use App\Metric\MetricsCollector\HttpOutboundMetricsCollector;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClientTest extends KernelTestCase
{
    /**
     * Тест списка магазинов.
     */
    public function testRequestStores(): void
    {
        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get('serializer');
        /** @var LoggerInterface $logger */
        $logger = self::getContainer()->get('logger');
        /** @var HttpOutboundMetricsCollector $metricsCollector */
        $metricsCollector = self::getContainer()->get(HttpOutboundMetricsCollector::class);

        $jsonResponse = '{"stores": ["901185","901514","901887"]}';

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getContent')
            ->with(true)
            ->willReturn($jsonResponse);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->method('request')
            ->willReturn($response);

        $result = (new ApiClient('http://test.local', $httpClient, $serializer, $logger, $metricsCollector))->requestStores();

        self::assertIsArray($result);
        self::assertNotEmpty($result);
    }
}
