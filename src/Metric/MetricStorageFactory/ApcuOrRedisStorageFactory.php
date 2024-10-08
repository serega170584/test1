<?php

declare(strict_types=1);

namespace App\Metric\MetricStorageFactory;

use Artprima\PrometheusMetricsBundle\StorageFactory\ApcFactory;
use Artprima\PrometheusMetricsBundle\StorageFactory\RedisFactory;
use Artprima\PrometheusMetricsBundle\StorageFactory\StorageFactoryInterface;
use Prometheus\Storage\Adapter;

class ApcuOrRedisStorageFactory implements StorageFactoryInterface
{
    public function __construct(
        private readonly ApcFactory $apcFactory,
        private readonly RedisFactory $redisFactory,
    ) {
    }

    public function getName(): string
    {
        return 'apcu-or-redis';
    }

    public function create(array $options): Adapter
    {
        if (PHP_SAPI === 'cli') {
            return $this->redisFactory->create($options);
        }

        return $this->apcFactory->create($options);
    }
}
