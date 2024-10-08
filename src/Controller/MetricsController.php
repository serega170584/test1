<?php

namespace App\Controller;

use Artprima\PrometheusMetricsBundle\Metrics\Renderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Рендер метрик.
 */
class MetricsController extends AbstractController
{
    /**
     * @Route("/metrics", name="app.metrics_index", methods={"GET"})
     */
    public function metrics(Renderer $renderer): Response
    {
        return new Response(
            $renderer->render(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/plain',
            ]
        );
    }
}
