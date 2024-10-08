<?php

declare(strict_types=1);

namespace App\ParamConverter;

use App\Exception\ValidationException;
use App\Request\AutowiredRequestInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AutowiredRequestParamConverter implements ParamConverterInterface
{
    public function __construct(
        private DenormalizerInterface $denormalizer,
        private ValidatorInterface $validator
    ) {
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        try {
            $data = array_merge($request->toArray(), $request->query->all());
            $specifiedRequest = $this->denormalizer->denormalize($data, $configuration->getClass());
        } catch (NotNormalizableValueException $e) {
            throw new ValidationException(new ConstraintViolationList([new ConstraintViolation('Некорректный тип.', '', [], null, $e->getPath(), null)]));
        } catch (ExceptionInterface) {
            throw new BadRequestException('Некорректный запрос');
        }

        $errors = $this->validator->validate($specifiedRequest);
        if ($errors->count()) {
            throw new ValidationException($errors);
        }

        $request->attributes->set($configuration->getName(), $specifiedRequest);
    }

    public function supports(ParamConverter $configuration): bool
    {
        if (!$configuration->getClass()) {
            return false;
        }

        return in_array(AutowiredRequestInterface::class, class_implements($configuration->getClass()), true);
    }
}
