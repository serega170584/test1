<?php

declare(strict_types=1);

namespace App\Controller;

use App\Client\Monolith\PROVIDER\ApiClient;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PersonalController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/api/personal/auth/', methods: ['POST'])]
    public function auth(
        Request $request,
        ApiClient $monolithClient
    ): Response {
        $json = $request->getContent();
        $jsonData = [];
        if ($json) {
            try {
                $jsonData = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
            }
        }

        $login = $request->get('login', $jsonData['login'] ?? '');
        $password = $request->get('password', $jsonData['password'] ?? '');
        $remember = (bool) $request->get('remember', $jsonData['remember'] ?? false);

        return JsonResponse::fromJsonString($monolithClient->auth($login, $password, $remember));
    }
}
