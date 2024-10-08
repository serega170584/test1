<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Ошибка валидации данных.
 */
class ValidationException extends BadRequestHttpException implements ErrorDetailsInterface, TranslatableInterface
{
    private array $errors = [];

    public function __construct(
        ConstraintViolationListInterface $validationErrors,
        ?string $message = 'VALIDATION_FAILED'
    ) {
        parent::__construct($message, null, 0, []);
        $this->processErrors($validationErrors);
    }

    public function getErrorDetails(): array
    {
        return $this->errors;
    }

    private function processErrors(ConstraintViolationListInterface $validationErrors): void
    {
        /** @var ConstraintViolation $error */
        foreach ($validationErrors as $error) {
            $this->errors[$error->getPropertyPath()][] = $error->getMessage();
        }
    }
}
