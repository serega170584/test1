<?php

declare(strict_types=1);

namespace App\Metric\MetricsCollector;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Prometheus\Exception\MetricsRegistrationException;

class DatabaseQueuesCountMessagesMetricsCollector implements MetricsCollectorInterface
{
    use MetricsCollectorInitTrait;

    // TODO подумать над автоматическим формированием списка очередей в БД
    private const QUEUE_NAMES = [
        'failed',
        'remove_order_reserves',
        'PROVIDER_goods_request',
        'PROVIDER_goods_process',
        'PROVIDER_prices_process',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @throws Exception
     * @throws MetricsRegistrationException
     */
    public function collect(): void
    {
        $result = $this->entityManager
            ->getConnection()
            ->executeQuery('SELECT COUNT(*) AS count, queue_name FROM messenger_messages GROUP BY queue_name');

        $foundQueueNames = [];
        foreach ($result->iterateAssociative() as $row) {
            $this->setMetric($row['count'], $row['queue_name']);
            $foundQueueNames[] = $row['queue_name'];
        }

        $notFoundQueueNames = array_diff(self::QUEUE_NAMES, $foundQueueNames);
        foreach ($notFoundQueueNames as $queueName) {
            $this->setMetric(0, $queueName);
        }
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function setMetric(int $count, string $queueName): void
    {
        $metric = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'database_queues_count_messages',
            'Database queues count messages',
            ['queue_name']
        );
        $metric->set($count, [$queueName]);
    }
}
