<?php

declare(strict_types=1);

namespace App\Service;

use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class FailedMessagesService
{
    private Application $console;

    private TransportInterface $failedTransport;

    public function __construct(
        KernelInterface $kernel,
        TransportInterface $failedTransport
    ) {
        $this->console = new Application($kernel); // Если подключить Application через конструктор, то тесты зависают
        $this->console->setAutoExit(false);
        $this->failedTransport = $failedTransport;
    }

    /**
     * Возвращает количество сообщений в транспорте failed.
     */
    public function getCountMessages(): int
    {
        if ($this->failedTransport instanceof MessageCountAwareInterface) {
            return $this->failedTransport->getMessageCount();
        }

        // Для тестов
        if ($this->failedTransport instanceof InMemoryTransport) {
            return count($this->failedTransport->get());
        }

        throw new RuntimeException('Failed transport not implements ' . MessageCountAwareInterface::class);
    }

    /**
     * @throws Exception
     */
    public function processMessages(int $limit): void
    {
        if (!$limit) {
            throw new RuntimeException('Limit cannot be empty');
        }

        $input = new ArrayInput([
            'command' => 'messenger:consume',
            'receivers' => ['failed'],
            '--limit' => $limit,
            '--time-limit' => 60 * 60, // Чтобы процесс не висел очень долго, если выставят большой лимит по количеству
        ]);
        $output = new BufferedOutput();

        $this->console->run($input, $output);
    }
}
