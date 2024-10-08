<?php

namespace App\Client;

use App\Exception\ApiClientException;
use App\Metric\MetricsCollector\HttpOutboundMetricsCollector;
use Prometheus\Exception\MetricsRegistrationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Базовый класс API-клиента.
 */
class BaseApiClient
{
    /**
     * Базовый адрес API.
     */
    private string $baseUri;

    private HttpClientInterface $httpClient;

    private SerializerInterface $serializer;

    private LoggerInterface $logger;

    public function __construct(
        string $baseUri,
        HttpClientInterface $httpClient,
        SerializerInterface $serializer,
        LoggerInterface $apiRequestLogger,
        private readonly HttpOutboundMetricsCollector $metricsCollector
    ) {
        $this->httpClient = $httpClient;
        $this->serializer = $serializer;
        $this->baseUri = $baseUri;
        $this->logger = $apiRequestLogger;
    }

    /**
     * Выполняет запрос к API. При успешном выполнении вернет строку с json-данными.
     *
     * @throws ApiClientException|TransportExceptionInterface
     * @throws MetricsRegistrationException
     */
    public function request(ApiRequest $request): string
    {
        $this->metricsCollector->start();
        $response = null;

        $request->setBaseUri($this->baseUri);
        try {
            if (str_contains($request->getUri(), '/personal/auth')) {
                $bodyArray = json_decode($request->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $bodyArray['password'] = '***';
                $bodyLog = json_encode($bodyArray, JSON_THROW_ON_ERROR);
            } else {
                $bodyLog = $request->getBody();
            }

            $this->logger->info(
                sprintf('Request: "%s %s"', $request->getMethod(), $request->getUri()),
                [
                    'headers' => $request->getHeaders(),
                    'body' => $bodyLog,
                ]
            );

            $response = $this->httpClient->request($request->getMethod(), $request->getUri(), [
                'headers' => $request->getHeaders(),
                'body' => $request->getBody(),
            ]);
            $content = $response->getContent();

            $this->logger->info(
                sprintf('Response: "%s %s"', $response->getInfo('http_code'), $response->getInfo('url')),
                [
                    'body' => $content,
                ]
            );

            return $content;
        } catch (HttpExceptionInterface $exception) {
            $response = $exception->getResponse();
            $this->logger->info(
                sprintf('Response: "%s %s"', $response->getInfo('http_code'), $response->getInfo('url')),
                [
                    'body' => $response->getContent(false),
                ]
            );

            throw new ApiClientException($exception->getMessage(), $request, $exception->getResponse(), $exception);
        } finally {
            $this->metricsCollector->stop($request, $response);
        }
    }

    protected function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }
}
