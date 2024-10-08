<?php

namespace App\Client\Monolith\PROVIDER;

use App\Client\ApiRequest;
use App\Client\BaseApiClient;
use App\Request\Dto\PROVIDER\v1\OrderDto;
use App\Request\Dto\PROVIDER\v1\OrderReceiptDto;
use App\Request\Dto\PROVIDER\v2\SetFullImportRemainCounter;
use test1\Message\V2\ConfirmExportedOrder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Клиент API PROVIDER монолита.
 */
class ApiClient extends BaseApiClient
{
    // Пробрасываемый заголовок авторизации
    public const PROXY_HEADER_AUTH = 'Authorization';

    public const SET_FULL_IMPORT_COUNT_PATH = '/v2/prov/import_remain_counter/PROVIDER_full_import/set';

    public const RESET_FULL_IMPORT_COUNT_PATH = '/v2/prov/import_remain_counter/PROVIDER_full_import/reset';

    /**
     * Возвращает список ID активных аптек.
     *
     * @version 1
     */
    public function requestStores(): array
    {
        $request = new ApiRequest(Request::METHOD_GET, '/PROVIDER/stores');
        $json = json_decode($this->request($request), true);

        return $json['stores'] ?? [];
    }

    /**
     * Запрос на обновление статуса заказа.
     *
     * @version 1
     */
    public function updateOrderStatus(OrderDto $order, array $headers = []): void
    {
        $this->request(
            new ApiRequest(
                Request::METHOD_PATCH,
                '/PROVIDER/orders/update-status',
                $headers,
                $this->getSerializer()->serialize($order, 'json')
            )
        );
    }

    /**
     * Завершение заказа.
     *
     * @version 1
     */
    public function completeOrder(OrderReceiptDto $orderReceipt, array $headers = []): void
    {
        $this->request(
            new ApiRequest(
                Request::METHOD_PATCH,
                '/PROVIDER/orders/complete-order',
                $headers,
                $this->getSerializer()->serialize($orderReceipt, 'json')
            )
        );
    }

    /**
     * Подтверждение обработки заказа.
     *
     * @version 2
     */
    public function confirmOrder(ConfirmExportedOrder $exportedOrder, array $headers = []): void
    {
        $this->request(
            new ApiRequest(
                Request::METHOD_POST,
                '/v2/prov/orders/confirm',
                $headers,
                $this->getSerializer()->serialize($exportedOrder, 'json')
            )
        );
    }

    /**
     * Авторизация в монолите.
     *
     * @throws TransportExceptionInterface
     */
    public function auth(string $login, string $password, bool $remember): string
    {
        return $this->request(
            new ApiRequest(
                Request::METHOD_POST,
                '/personal/auth/',
                [],
                $this->getSerializer()->serialize(
                    [
                        'login' => $login,
                        'password' => $password,
                        'remember' => $remember,
                    ],
                    'json'
                )
            )
        );
    }

    /**
     * Сохранение счетчика количества обработанных сообщений, аптек на стороне монолита.
     *
     * @throws TransportExceptionInterface
     */
    public function setFullImportCount(SetFullImportRemainCounter $request, array $headers = []): void
    {
        $this->request(
            new ApiRequest(
                Request::METHOD_POST,
                self::SET_FULL_IMPORT_COUNT_PATH,
                $headers,
                $this->getSerializer()->serialize($request, 'json')
            )
        );
    }

    /**
     * Сброс счетчика количества обработанных сообщений, аптек на стороне монолита.
     *
     * @throws TransportExceptionInterface
     */
    public function resetFullImportRemainCount(array $headers = []): void
    {
        $this->request(
            new ApiRequest(
                Request::METHOD_POST,
                self::RESET_FULL_IMPORT_COUNT_PATH,
                $headers
            )
        );
    }
}
