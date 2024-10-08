<?php

namespace App\Controller\FakeApi\PROVIDER\v1;

use App\Client\PROVIDER\DivisionsItemsDto;
use App\Client\PROVIDER\OrderStatusesDto;
use App\Request\Dto\PROVIDER\v1\DivisionDto;
use App\Request\Dto\PROVIDER\v1\DivisionItemDto;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Эмуляция запросов к API v1 УАС.
 *
 * @Route("/e-shop", name="_v1")
 */
class EshopController extends AbstractController
{
    /**
     * Частичные остатки.
     *
     * @Rest\Post("/items", name="_items")
     * @ParamConverter("items", converter="fos_rest.request_body")
     */
    public function items(DivisionsItemsDto $items, SerializerInterface $serializer): JsonResponse
    {
        $result = [];

        foreach ($items->getDivisions() as $divisionId) {
            $divisionItems = [];

            for ($i = 1; $i < 10; $i++) {
                $divisionItems[] = (new DivisionItemDto())
                    ->setCode('100027631' . $i)
                    ->setPrice(rand(10, 100) + rand(1, 10) / 10)
                    ->setQuantity(rand(0, 50));
            }

            $result[] = (new DivisionDto())
                ->setDivision($divisionId)
                ->setItems($divisionItems);
        }

        return new JsonResponse($serializer->serialize($result, 'json'), 200, [], true);
    }

    /**
     * Запрос статусов для асинхронного обновления.
     *
     * @Rest\Put("/update-status", name="_update_status")
     * @ParamConverter("statuses", converter="fos_rest.request_body")
     */
    public function getStatuses(OrderStatusesDto $statuses): JsonResponse
    {
        return new JsonResponse([
            'id' => $statuses->getId(),
            'time' => date('d.m.Y H:i:s'),
        ]);
    }

    /**
     * Создание заказа.
     *
     * @Rest\Put("/orders/{number}")
     */
    public function createOrder(): JsonResponse
    {
        // @todo implement
        return $this->json('');
    }

    /**
     * Обновление заказа.
     *
     * @Rest\Patch("/orders/{number}")
     */
    public function updateOrder(): JsonResponse
    {
        // @todo implement
        return $this->json('');
    }

    /**
     * Переключени активной зоны.
     *
     * @Rest\Post("/zone")
     */
    public function setZone(): JsonResponse
    {
        return $this->json(['success' => true]);
    }
}
