<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VersionController extends AbstractController
{
    /**
     * @Route("/version", name="app.version")
     */
    public function actionVersion(): Response
    {
        try {
            $version = $this->getParameter('app.version');
        } catch (\InvalidArgumentException $_) {
            $version = 'unknown';
        }

        return new JsonResponse(['version' => $version]);
    }
}
