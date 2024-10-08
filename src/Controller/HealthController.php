<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Health роутинг.
 */
class HealthController extends AbstractController
{
    /**
     * @Route("/health/", name="app.health_check")
     */
    public function health(Connection $connection): Response
    {
        $connection->connect();

        return new JsonResponse([
            'status' => [
                'databaseConnection' => true,
            ],
        ]);
    }

    /**
     * Endpoint для live пробы k8s.
     *
     * @Route("/alive", name="app.alive")
     */
    public function alive(): Response
    {
        return new JsonResponse([
            'status' => 'ok',
        ]);
    }

    /**
     * Endpoint для ready пробы k8s.
     *
     * @Route("/ready", name="app.ready")
     */
    public function ready(Connection $connection): Response
    {
        // Для ready дополнительно проверим коннект к базе
        $connection->connect();

        return new JsonResponse([
            'status' => 'ok',
        ]);
    }
}
