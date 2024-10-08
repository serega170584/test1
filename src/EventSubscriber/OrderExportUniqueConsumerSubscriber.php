<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Worker;

/**
 * Subscriber, который позволяет запускать только один консюмер v2_export_orders
 * в один момент времени.
 *
 * Остальные запущенные консюмеры должны ждать пока остановится тот, который работает сейчас
 */
class OrderExportUniqueConsumerSubscriber implements EventSubscriberInterface
{
    private const TRANSPORT_ORDER_EXPORT = 'v2_export_orders';

    private LockFactory $lockFactory;
    private LoggerInterface $logger;

    private LockInterface $currentLock;

    public function __construct(LockFactory $lockFactory, LoggerInterface $logger)
    {
        $this->lockFactory = $lockFactory;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            WorkerStartedEvent::class => 'onWorkerStart',
            WorkerStoppedEvent::class => 'onWorkerStop',
        ];
    }

    public function onWorkerStart(WorkerStartedEvent $event): void
    {
        if (!$this->isApplicable($event->getWorker())) {
            return;
        }

        $lockName = self::TRANSPORT_ORDER_EXPORT;

        $this->logger->info('consumer.uniq: trying to get lock', ['name' => $lockName]);
        $this->currentLock = $this->lockFactory->createLock($lockName, null);
        if (!$this->currentLock->acquire(true)) {
            throw new \RuntimeException("Failed to get lock $lockName");
        }

        $this->logger->info('consumer.uniq: got lock', ['name' => $lockName]);
    }

    public function onWorkerStop(WorkerStoppedEvent $event): void
    {
        if (!$this->isApplicable($event->getWorker())) {
            return;
        }

        $this->currentLock->release();
        $this->logger->info('consumer.uniq: lock released', ['name' => $this->currentLock]);
    }

    private function isApplicable(Worker $worker): bool
    {
        foreach ($worker->getMetadata()->getTransportNames() as $transportName) {
            if (self::TRANSPORT_ORDER_EXPORT === $transportName) {
                return true;
            }
        }

        return false;
    }
}
