<?php

namespace App\Logger;

use Symfony\Component\Uid\Uuid;

/**
 * Общий процессор логов.
 */
class AppProcessor
{
    private string $appName;
    private string $uid;

    public function __construct(string $appName)
    {
        $this->appName = $appName;
        $this->uid = Uuid::v4()->toBase58();
    }

    public function __invoke(array $records): array
    {
        // Название приложения
        $records['extra']['component_name'] = $this->appName;

        // Переносим поля из контекста в экстру по требованию документации
        if ($traceId = $records['context']['trace_id'] ?? null) {
            $records['extra']['trace_id'] = $traceId;
            unset($records['context']['trace_id']);
        }

        // UID локальный
        $records['extra']['uid'] = $this->uid;

        return $records;
    }
}
