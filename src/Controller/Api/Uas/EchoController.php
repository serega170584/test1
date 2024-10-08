<?php

namespace App\Controller\Api\PROVIDER;

use App\Exception\ValidationException;
use App\Request\Dto\PROVIDER\EchoRequestDto;
use App\Response\Result\SuccessResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EchoController extends AbstractController
{
    /**
     * Тестовое АПИ для авторизации УАСом в ФА.
     */
    #[Route('/echo', methods: ['POST'])]
    public function echo(Request $request, ValidatorInterface $validator, SerializerInterface $serializer): JsonResponse
    {
        /** @var EchoRequestDto $echoRequestDto */
        $echoRequestDto = $serializer->deserialize(
            $request->getContent(),
            EchoRequestDto::class,
            'json'
        );

        $errors = $validator->validate($echoRequestDto);

        if ($errors->count()) {
            throw new ValidationException($errors);
        }

        return $this->json(new SuccessResult($echoRequestDto->getMessage()));
    }
}
