<?php

namespace App\Tests\Controller\PROVIDER;

use App\Message\PROVIDERGoodsProcess;
use App\Message\PROVIDERPricesProcess;
use App\Request\Dto\PROVIDER\v1\DivisionDto;
use App\Request\Dto\PROVIDER\v1\DivisionItemDto;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Serializer\SerializerInterface;

class GoodsControllerTest extends WebTestCase
{
    /**
     * Вернет тестовый список из N аптек для частичного импорта.
     *
     * @return DivisionDto[]
     */
    private function fillDivisions(int $count): array
    {
        $divisions = [];

        for ($d = 1; $d <= $count; $d++) {
            $divisionItems = [];

            for ($i = 1; $i <= 2; $i++) {
                $item = (new DivisionItemDto())
                    ->setCode('90000000' . $d . $i)
                    ->setPrice(rand(10, 100) + rand(1, 10) / 10)
                    ->setQuantity(rand(0, 50));

                $divisionItems[] = $item;
            }

            $divisions[] = (new DivisionDto())
                ->setDivision('90000' . $d)
                ->setItems($divisionItems);
        }

        return $divisions;
    }

    /**
     * Вернет тестовый список аптек с некорректными данными.
     */
    private function fillBadDivisions(): array
    {
        $badVars = [
            'Division' => ['', '0', '-904801', 'hdfuwfh734', 'null'],
            'Code' => ['', '0', '-1000339988', '12dfsf8hsfwf', 'null'],
            'Price' => ['', '0', '-44', '1212er', 'null'],
            'Quantity' => ['', '-10', '777EEEEEEEEE', 'null'],
        ];

        // Нормальное значение
        $normalValue = [
            'Division' => '904801',
            'items' => [
                [
                    'Code' => '1000339988',
                    'Price' => 88,
                    'Quantity' => 10,
                ],
            ],
        ];

        $divisions = [];

        // Некорректные ID аптек
        foreach ($badVars['Division'] as $value) {
            $divisions[] = array_merge($normalValue, [
                'Division' => $value,
            ]);
        }

        // Пустой items
        $divisions[] = array_merge($normalValue, ['items' => []]);

        // Некорректные артикулы
        foreach ($badVars['Code'] as $value) {
            $divisions[] = array_merge($normalValue, [
                'items' => [
                    'Code' => $value,
                    'Price' => 99.99,
                    'Quantity' => 10,
                ],
            ]);
        }

        // Отсутствует поле Code
        $divisions[] = array_merge($normalValue, [
            'items' => ['Price' => 99.99, 'Quantity' => 10],
        ]);

        // Некорректные цены
        foreach ($badVars['Price'] as $value) {
            $divisions[] = array_merge($normalValue, [
                'items' => [
                    'Code' => '1000339988',
                    'Price' => $value,
                    'Quantity' => 10,
                ],
            ]);
        }

        // Отсутствует поле Price или Quantity
        $divisions[] = array_merge($normalValue, [
            'items' => [
                'Code' => '1000339988',
            ],
        ]);

        // Некорректные остатки
        foreach ($badVars['Quantity'] as $value) {
            $divisions[] = array_merge($normalValue, [
                'items' => [
                    'Code' => '1000339988',
                    'Price' => 99.99,
                    'Quantity' => $value,
                ],
            ]);
        }

        return $divisions;
    }

    private function getSerializer(): SerializerInterface
    {
        return self::getContainer()->get('serializer');
    }

    /**
     * Частичный импорт данных (успех).
     */
    private function testImportPositive(string $requestUri, array $divisions): void
    {
        $client = static::createClient();

        $json = $this->getSerializer()->serialize($divisions, 'json');

        $client->request('POST', $requestUri, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $json);

        $response = $client->getResponse();
        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * Частичный импорт с невалидными данными.
     *
     * @throws JsonException
     */
    private function testImportBadRequest(string $requestUri): void
    {
        $client = static::createClient();

        foreach ($this->fillBadDivisions() as $division) {
            $client->request('POST', $requestUri, [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([$division], JSON_THROW_ON_ERROR));

            $response = $client->getResponse();
            self::assertSame(400, $response->getStatusCode());

            $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            self::assertIsArray($responseData);
            self::assertArrayHasKey('message', $responseData);
            self::assertArrayHasKey('errors', $responseData);
        }
    }

    /**
     *
     * Частичный импорт цен (успех).

    public function testImportPrices(): void
    {
        $divisions = $this->fillDivisions(3);

        $this->testImportPositive('/api/PROVIDER/v1/goods/prices', $divisions);

        /** @var InMemoryTransport $transport * /
        $transport = self::getContainer()->get('messenger.transport.PROVIDER_prices_process');

        $envelope = $transport->getSent()[0] ?? null;
        $producedMessage = $envelope?->getMessage();
        $expectedMessage = (new PROVIDERPricesProcess($divisions));

        self::assertEquals($expectedMessage, $producedMessage);
    }
    */
    /**
     * Частичный импорт остатков (успех).
     */
    public function testImportRemains(): void
    {
        $divisions = $this->fillDivisions(3);

        $this->testImportPositive('/api/PROVIDER/v1/goods/remains', $divisions);

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.PROVIDER_goods_process');

        $envelope = $transport->getSent()[0] ?? null;
        $producedMessage = $envelope?->getMessage();
        $expectedMessage = (new PROVIDERGoodsProcess($divisions));

        self::assertEquals($expectedMessage, $producedMessage);
    }

    /**
     * Частичный импорт цен с невалидными данными (400 ответ).
     *
     * @throws JsonException

    public function testImportPricesBadRequest(): void
    {
        $this->testImportBadRequest('/api/PROVIDER/v1/goods/prices');
    }
     * /

    /**
     * Частичный импорт остатков с невалидными данными (400 ответ).
     *
     * @throws JsonException
     */
    public function testImportRemainsBadRequest(): void
    {
        $this->testImportBadRequest('/api/PROVIDER/v1/goods/remains');
    }
}
