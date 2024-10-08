<?php

namespace App\Controller\Api\PROVIDER\v1;

use App\Exception\ValidationException;
use App\Message\PROVIDERGoodsProcess;
use App\Message\PROVIDERPricesProcess;
use App\Request\Dto\PROVIDER\v1\DivisionDto;
use App\Response\Result\ErrorResult;
use App\Response\Result\SuccessResult;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Частичный импорт из УАС
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
 * @OA\Tag(name="goods")
 */
class GoodsController extends AbstractController
{
    /**
     * Частичный импорт цен.
     *
     * @Rest\Post("/v1/goods/prices", name="_prices")
     * @Rest\Post("/goods/prices", name="_goods_prices")
     * @ParamConverter(
     *     "divisions",
     *     class="App\Request\Dto\PROVIDER\v1\DivisionDto[]", converter="fos_rest.request_body",
     *     options={"validator": {"groups": {"prices", "Default"}}}
     * )
     *
     * @OA\RequestBody(
     *     description="Список атпек",
     *     required=true,
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=DivisionDto::class))
     *     )
     * )
     * @OA\Tag(name="prices")
     */
    public function prices(
        array $divisions,
        MessageBusInterface $bus,
        TranslatorInterface $translator,
        ConstraintViolationListInterface $validationErrors
    ): Response {
        //return new Response();

        //if (count($validationErrors)) {
            //throw new ValidationException($validationErrors);
        //}

        //$bus->dispatch(new PROVIDERPricesProcess($divisions));

        return $this->json(new SuccessResult($translator->trans('response.remains.import_success')));
    }

    /**
     * Частичный импорт остатков.
     *
     * @Rest\Post("/v1/goods/remains", name="_remains")
     * @Rest\Post("/goods/remains", name="_goods_remains")
     * @ParamConverter(
     *     "divisions",
     *     class="App\Request\Dto\PROVIDER\v1\DivisionDto[]", converter="fos_rest.request_body",
     *     options={"validator": {"groups": {"remains", "Default"}}}
     * )
     *
     * @OA\RequestBody(
     *     description="Список атпек",
     *     required=true,
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=DivisionDto::class))
     *     )
     * )
     * @OA\Tag(name="remains")
     */
    public function remains(
        array $divisions,
        ConstraintViolationListInterface $validationErrors,
        MessageBusInterface $bus,
        TranslatorInterface $translator
    ): Response {
        if (count($validationErrors)) {
            throw new ValidationException($validationErrors);
        }

        $bus->dispatch(new PROVIDERGoodsProcess($divisions));

        return $this->json(new SuccessResult($translator->trans('response.remains.import_success')));
    }
}
