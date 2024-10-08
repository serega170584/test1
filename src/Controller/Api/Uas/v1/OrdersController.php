<?php

namespace App\Controller\Api\PROVIDER\v1;

use App\Client\Monolith\PROVIDER\ApiClient;
use App\Exception\ValidationException;
use App\Request\Dto\PROVIDER\v1\OrderReceiptDto;
use App\Response\Result\ErrorResult;
use App\Response\Result\SuccessResult;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Обновление заказов из УАС
 *
 * @Route("/v1/orders", name="_v1_orders")
 *
 * @OA\Response(
 *     response=200,
 *     description="Успешная обработка",
 *     @Model(type=SuccessResult::class)
 * )
 * @OA\Response(
 *     response=400,
 *     description="Ошибка валидации",
 *     @Model(type=ErrorResult::class)
 * )
 * @OA\Response(
 *     response=401,
 *     description="Ошибка авторизации",
 *     @Model(type=ErrorResult::class)
 * )
 * @OA\Response(
 *     response=500,
 *     description="Внутренняя ошибка сервера",
 *     @Model(type=ErrorResult::class)
 * )
 * @OA\Tag(name="orders")
 * @Security(name="basic")
 *
 * @deprecated Этот функционал так и не был запущен
 */
class OrdersController extends AbstractController
{
    /**
     * Выдача заказа.
     *
     * @Rest\Patch("/complete", name="_complete")
     * @ParamConverter("orderReceipt", converter="fos_rest.request_body", options={"validator": {"groups": {"complete", "Default"}}})
     *
     * @OA\RequestBody(
     *     description="Чек заказа",
     *     required=true,
     *     @OA\JsonContent(ref=@Model(type=OrderReceiptDto::class))
     * )
     * @OA\Tag(name="complete")
     */
    public function complete(
        OrderReceiptDto $orderReceipt,
        ConstraintViolationListInterface $validationErrors,
        TranslatorInterface $translator,
        ApiClient $apiClient,
        Request $request
    ): JsonResponse {
        if (count($validationErrors)) {
            throw new ValidationException($validationErrors);
        }

        $apiClient->completeOrder($orderReceipt, [
            ApiClient::PROXY_HEADER_AUTH => $request->server->get('HTTP_AUTHORIZATION'),
        ]);

        return $this->json(new SuccessResult($translator->trans('response.orders.complete_success')));
    }
}
