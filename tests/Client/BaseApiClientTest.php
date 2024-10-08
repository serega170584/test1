<?php

namespace App\Tests\Client;

use App\Client\ApiRequest;
use App\Client\BaseApiClient;
use App\Exception\ApiClientExceptionInterface;
use App\Metric\MetricsCollector\HttpOutboundMetricsCollector;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BaseApiClientTest extends KernelTestCase
{
    /**
     * Слепок запроса.
     */
    private function createRequestMock(): MockObject
    {
        $request = $this->createMock(ApiRequest::class);

        $request->method('getUri')->willReturn('http://test.local/api/test-request');
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn('{}');
        $request->method('getHeaders')->willReturn(['X-TEST' => 1]);

        return $request;
    }

    /**
     * Тест запроса.
     * @throws TransportExceptionInterface
     */
    public function testRequest(): void
    {
        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get('serializer');
        /** @var LoggerInterface $logger */
        $logger = self::getContainer()->get('logger');
        /** @var HttpOutboundMetricsCollector $metricsCollector */
        $metricsCollector = self::getContainer()->get(HttpOutboundMetricsCollector::class);

        /** @var ApiRequest $request */
        $request = $this->createRequestMock();

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getContent')
            ->willReturn('{}');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->method('request')
            ->willReturn($response);

        $result = (new BaseApiClient('http://test.local', $httpClient, $serializer, $logger, $metricsCollector))->request(
            $request
        );

        self::assertSame('{}', $result);
    }

    /**
     * Тест исключения.
     * @throws TransportExceptionInterface
     */
    public function testException(): void
    {
        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get('serializer');
        /** @var LoggerInterface $logger */
        $logger = self::getContainer()->get('logger');
        /** @var HttpOutboundMetricsCollector $metricsCollector */
        $metricsCollector = self::getContainer()->get(HttpOutboundMetricsCollector::class);

        /** @var ApiRequest $request */
        $request = $this->createRequestMock();

        $exception = $this->createMock(HttpExceptionInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getContent')
            ->with(true)
            ->willThrowException($exception);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->method('request')
            ->willReturn($response);

        $this->expectException(ApiClientExceptionInterface::class);
        (new BaseApiClient('http://test.local', $httpClient, $serializer, $logger, $metricsCollector))->request($request);
    }
}
