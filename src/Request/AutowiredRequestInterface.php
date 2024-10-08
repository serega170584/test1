<?php

declare(strict_types=1);

namespace App\Request;

use App\ParamConverter\AutowiredRequestParamConverter;

/**
 * Нужен для автоматической сериализации и валидации через ParamConverter.
 *
 * @see AutowiredRequestParamConverter
 */
interface AutowiredRequestInterface
{
}
