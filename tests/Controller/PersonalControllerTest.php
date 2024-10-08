<?php

namespace App\Tests\Controller;

use Exception;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PersonalControllerTest extends WebTestCase
{
    /**
     * @dataProvider authDataProvider
     * @throws JsonException
     */
    public function testAuth(string $login, string $password, bool $remember): void
    {
        $client = static::createClient();

        $this->initMonolithClientMock($login, $password, $remember);

        $client->request(
            'POST',
            '/api/personal/auth/',
            [
                'login' => $login,
                'password' => $password,
                'remember' => $remember,
            ],
        );
        $responseJson = $client->getResponse()->getContent();
        $responseData = json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
        self::assertResponseIsSuccessful();
        self::assertNotEmpty($responseData['data']['accessToken']);
    }

    /**
     * @dataProvider authDataProvider
     * @throws JsonException
     */
    public function testAuthByJson(string $login, string $password, bool $remember): void
    {
        $client = static::createClient();

        $this->initMonolithClientMock($login, $password, $remember);

        $body = json_encode(
            [
                'login' => $login,
                'password' => $password,
                'remember' => $remember,
            ],
            JSON_THROW_ON_ERROR
        );

        $client->request(
            'POST',
            '/api/personal/auth/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body
        );
        $responseJson = $client->getResponse()->getContent();
        $responseData = json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);

        self::assertResponseIsSuccessful();
        self::assertNotEmpty($responseData['data']['accessToken']);
    }

    /**
     * @throws Exception
     */
    public function authDataProvider(): array
    {
        return [
            'remember' => ['login_' . random_int(1, 10), bin2hex(random_bytes(10)), true],
            'not_remember' => ['login_' . random_int(1, 10), bin2hex(random_bytes(10)), false],
        ];
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    private function initMonolithClientMock(string $login, string $password, bool $remember): void
    {
        $responseStub = $this->createStub(ResponseInterface::class);
        $responseStub
            ->method('getContent')
            ->willReturn(
                json_encode([
                    "message" => "Вы успешно авторизованы",
                    "data" => [
                        'accessToken' => bin2hex(random_bytes(10)),
                        'accessExpire' => time(),
                        'refreshToken' => bin2hex(random_bytes(10)),
                        'refreshExpire' => time(),
                        'shops' => [],
                        'user' => [
                            'code' => '',
                            'phone' => '79' . random_int(100000000, 999999999),
                            'firstName' => $login,
                            'lastName' => $login,
                            'middleName' => $login,
                            'role' => [],
                            'ownersCourierServices' => 1,
                            'region' => null,
                            'id' => random_int(10000, 99999),
                            'assemblyCompany' => null,
                        ],
                    ],
                    'reload' => true,
                    'captcha' => false,
                ], JSON_THROW_ON_ERROR)
            );

        $expectRequestBody = json_encode(
            [
                'login' => $login,
                'password' => $password,
                'remember' => $remember,
            ],
            JSON_THROW_ON_ERROR
        );

        $monolithClient = $this->createMock(HttpClientInterface::class);
        $monolithClient
            ->method('request')
            ->with(
                'POST',
                'http://monolith/api/personal/auth/',
                [
                    'headers' => [],
                    'body' => $expectRequestBody,
                ]
            )
            ->willReturn($responseStub);

        self::getContainer()->set('test.monolith.PROVIDER.client', $monolithClient);
    }
}
