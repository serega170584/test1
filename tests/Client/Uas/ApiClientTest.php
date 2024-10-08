<?php

namespace App\Tests\Client\PROVIDER;

use App\Client\PROVIDER\ApiClient;
use App\Client\PROVIDER\DivisionsItemsDto;
use App\Metric\MetricsCollector\HttpOutboundMetricsCollector;
use App\Request\Dto\PROVIDER\v1\DivisionDto;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClientTest extends KernelTestCase
{
    /**
     * Тест запроса полных остатков.
     */
    public function testRequestDivisions(): void
    {
        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get('serializer');
        /** @var LoggerInterface $logger */
        $logger = self::getContainer()->get('logger');
        /** @var HttpOutboundMetricsCollector $metricsCollector */
        $metricsCollector = self::getContainer()->get(HttpOutboundMetricsCollector::class);

        $jsonResponse = '[
            {
                "Division": "904801",
                "items": [
                    {
                        "Code": "1000339988",
                        "Quantity": 100,
                        "Price": 100
                    }
                ]
            }
        ]';

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getContent')
            ->with(true)
            ->willReturn($jsonResponse);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->method('request')
            ->willReturn($response);

        $result = (new ApiClient('http://test.local', $httpClient, $serializer, $logger, $metricsCollector))->requestDivisions(
            new DivisionsItemsDto(['900590'])
        );

        self::assertIsArray($result);

        $dto = array_shift($result);
        self::assertInstanceOf(DivisionDto::class, $dto);
    }
}
