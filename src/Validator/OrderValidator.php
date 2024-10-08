<?php

namespace App\Validator;

use App\Request\Dto\PROVIDER\v1\OrderDto;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Валидатор заказов.
 */
class OrderValidator
{
    public static function validate(OrderDto $orderDto, ExecutionContextInterface $context, $payload)
    {
        // Для частичной выдачи должны быть указаны позиции
        if ($orderDto->isStatusPartReady() && !$orderDto->getRows()) {
            $context
                ->buildViolation('order.rows.empty')
                ->atPath('rows')
                ->addViolation();
        }
    }
}
