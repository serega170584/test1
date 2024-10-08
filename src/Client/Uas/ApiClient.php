<?php

namespace App\Client\PROVIDER;

use App\Client\ApiRequest;
use App\Client\BaseApiClient;
use App\Client\PROVIDER\v2\OrderDto;
use App\Client\PROVIDER\v2\SendSyncOrdersStatuses;
use App\Request\Dto\PROVIDER\v1\DivisionDto;
use App\Request\Dto\PROVIDER\v2\OrderResultDto;
use App\Request\Dto\PROVIDER\v2\RequestResultDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Клиент API УАС.
 */
class ApiClient extends BaseApiClient
{
    /**
     * Возвращает список аптек и остатков по ним.
     *
     * @return DivisionDto[]
     */
    public function requestDivisions(DivisionsItemsDto $divisionsDto): array
    {
        $requestJson = $this->getSerializer()->serialize($divisionsDto, 'json');
        $responseJson = $this->request(new ApiRequest(Request::METHOD_POST, '/e-shop/items', [], $requestJson));

        return $this->getSerializer()->deserialize($responseJson, DivisionDto::class . '[]', 'json');
    }

    /**
     * Запрос на получение статусов заказов в асинхронном режиме.
     */
    public function requestStatuses(OrderStatusesDto $statusesDto): RequestResultDto
    {
        $requestJson = $this->getSerializer()->serialize($statusesDto, 'json');
        $responseJson = $this->request(new ApiRequest(Request::METHOD_PUT, '/e-shop/update-status', [], $requestJson));

        return $this->getSerializer()->deserialize($responseJson, RequestResultDto::class, 'json');
    }

    /**
     * Создание нового заказа.
     */
    public function createOrder(OrderDto $orderDto): OrderResultDto
    {
        $requestJson = $this->getSerializer()->serialize($orderDto, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);

        $responseJson = $this->request(
            new ApiRequest(
                Request::METHOD_PUT,
                sprintf('/e-shop/v2/orders/%s', $orderDto->getNumber()),
                [],
                $requestJson
            )
        );

        return $this->getSerializer()->deserialize($responseJson, OrderResultDto::class, 'json', [
            'groups' => ['success'],
        ]);
    }

    /**
     * Обновление статуса заказа.
     */
    public function updateOrder(OrderDto $orderDto): OrderResultDto
    {
        $requestJson = $this->getSerializer()->serialize($orderDto, 'json', [
            'groups' => ['update', 'default'],
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);

        $responseJson = $this->request(
            new ApiRequest(
                Request::METHOD_PATCH,
                sprintf('/e-shop/v2/orders/%s', $orderDto->getNumber()),
                [],
                $requestJson
            )
        );

        return $this->getSerializer()->deserialize($responseJson, OrderResultDto::class, 'json', [
            'groups' => ['success'],
        ]);
    }

    /**
     * Переключение активной зоны на тестовой УАС
     */
    public function setActiveTestZone(string $zoneName)
    {
        $requestJson = json_encode(['Name' => $zoneName]);

        $this->request(
            new ApiRequest(
                Request::METHOD_POST,
                '/e-shop/zone',
                [],
                $requestJson
            )
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendSyncOrdersStatuses(SendSyncOrdersStatuses $request): void
    {
        $json = $this->getSerializer()->serialize($request, 'json');

        $this->request(
            new ApiRequest(
                Request::METHOD_POST,
                '/e-shop/v2/orders/sync-status',
                [],
                $json
            )
        );
    }
}
