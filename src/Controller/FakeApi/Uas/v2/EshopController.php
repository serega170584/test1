<?php

namespace App\Controller\FakeApi\PROVIDER\v2;

use App\Client\PROVIDER\v2\OrderDto;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Эмуляция запросов к API v2 УАС.
 *
 * @Route("/e-shop/v2", name="_v2")
 */
class EshopController extends AbstractController
{
    /**
     * Создание заказа.
     *
     * @Rest\Put("/orders/{number}", name="_create_order")
     * @ParamConverter("orderDto", converter="fos_rest.request_body")
     */
    public function createOrder(OrderDto $orderDto): JsonResponse
    {
        // Каждый 3й заказ не проходит валидацию
        if ((int) $orderDto->getNumber() % 3 === 0) {
            return $this->json([
                'Id' => $orderDto->getId(),
                'OrderId' => $orderDto->getNumber(),
                'Error' => [
                    [
                        'Code' => 'PARAMETERS_ARE_NOT_SPECIFIED',
                        'Message' => 'Required parameter not passed',
                    ],
                ],
            ], 400);
        }

        return $this->json([
            'Id' => $orderDto->getId(),
            'OrderId' => $orderDto->getNumber(),
            'Message' => 'Заказ создан',
        ], 201);
    }

    /**
     * Обновление заказа.
     *
     * @Rest\Patch("/orders/{number}", name="_update_order")
     * @ParamConverter("orderDto", converter="fos_rest.request_body")
     */
    public function updateOrder(OrderDto $orderDto): JsonResponse
    {
        // Каждый 3й заказ не проходит валидацию
        if ((int) $orderDto->getNumber() % 3 === 0) {
            return $this->json([
                'Id' => $orderDto->getId(),
                'OrderId' => $orderDto->getNumber(),
                'Error' => [
                    [
                        'Code' => 'PARAMETERS_ARE_NOT_SPECIFIED',
                        'Message' => 'Required parameter not passed',
                    ],
                ],
            ], 400);
        }

        return $this->json([
            'Id' => $orderDto->getId(),
            'OrderId' => $orderDto->getNumber(),
            'Message' => 'Заказ изменен',
        ], 200);
    }
}
