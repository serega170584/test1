<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthControllerTest extends WebTestCase
{
    public function testHealth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/health');
        self::assertResponseRedirects();

        $client->request('GET', '/health/');
        self::assertResponseIsSuccessful();
    }
}
