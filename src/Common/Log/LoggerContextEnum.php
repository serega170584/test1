<?php
declare(strict_types=1);

namespace App\Common\Log;

enum LoggerContextEnum: string
{
    case EXCEPTION = 'exception';
    case DATA = 'data';
}
