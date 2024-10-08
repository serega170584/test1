<?php

namespace App\Tests\MessageHandler;

use App\Manager\FeatureManager;
use App\Request\Dto\OrderStatusInterface;
use App\Request\Dto\OrderStatusInterface as ImOrderStatus;
use Exception;
use Flagception\Manager\FeatureManagerInterface;
use test1\Message\V1\ImportRemain;
use test1\Message\V2\ChangeRemainQuantity;
use test1\Message\V2\ExportOrder;
use test1\Message\V2\ImportOrder;
use test1\Message\V2\ImportOrderReceipt;
use test1\Message\V2\ImportOrderStatus;
use test1\Message\V2\SyncOrderStatuses;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MigrationToKafkaTest extends KernelTestCase
{
    private const EXPORT_ORDER_AMQP_TRANSPORT_NAME = 'v2_export_orders';

    private const EXPORT_ORDER_KAFKA_TRANSPORT_NAME = 'orders_export_kafka';

    private const SYNC_ORDER_STATUSES_AMQP_TRANSPORT_NAME = 'v2_sync_order_statuses';

    private const SYNC_ORDER_STATUSES_KAFKA_TRANSPORT_NAME = 'orders_sync_statuses_kafka';

    /**
     * @throws Exception
     */
    public function exportOrderProvider(): array
    {
        $message = new ExportOrder(
            (string)random_int(100000, 999999),
            ImOrderStatus::STATUS_CANCELLED,
            random_int(1, 2),
            date('Y-m-d H:i:s'),
            '7' . random_int(1000000000, 9999999999)
        );
        $message
            ->setRequestId(Uuid::v4());

        return [
            'is_disabled_kafka' => [$message, false],
            'is_enabled_kafka' => [$message, true],
        ];
    }

    /**
     * @throws Exception
     */
    public function syncOrderStatusesProvider(): array
    {
        $message = new SyncOrderStatuses(
            [
                (string)random_int(100000, 999999),
                (string)random_int(100000, 999999),
                (string)random_int(100000, 999999),
            ]
        );
        $message
            ->setRequestId(Uuid::v4());

        return [
            'is_disabled_kafka' => [$message, false],
            'is_enabled_kafka' => [$message, true],
        ];
    }

    private function initMockPROVIDERHttpClient(InvocationOrder $expectCountRequest): void
    {
        $PROVIDERClient = $this->createMock(HttpClientInterface::class);
        $PROVIDERClient
            ->expects($expectCountRequest)
            ->method('request');

        self::getContainer()->set('test.PROVIDER.client', $PROVIDERClient);
    }

    private function initMockFeatureManager(bool $isEnabledMonolithKafka): void
    {
        $featureManager = $this->createMock(FeatureManagerInterface::class);
        $featureManager
            ->method('isActive')
            ->willReturnMap(
                [
                    [FeatureManager::IS_ENABLED_MONOLITH_KAFKA, null, $isEnabledMonolithKafka],
                    [FeatureManager::IS_ENABLED_DELIVERY_TO_CUSTOMER, null, false],
                ]
            );

        self::getContainer()->set('flagception.manager.feature_manager', $featureManager);
    }

    private function sendMessageToTransport(object $message, string $transportName): void
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get("messenger.transport.{$transportName}");
        $transport->send(Envelope::wrap($message));
    }

    private function executeConsumeCommand(KernelInterface $kernel, string $receiverName): void
    {
        $application = new Application($kernel);
        $command = $application->find('messenger:consume');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'receivers' => [$receiverName],
            '--limit' => '1',
        ]);

        $commandTester->assertCommandIsSuccessful();
    }

    /**
     * @dataProvider exportOrderProvider
     * @param ExportOrder $message
     * @param             $isEnabledMonolithKafka
     * @return void
     */
    public function testAmqpExportOrderHandlingMessage(ExportOrder $message, $isEnabledMonolithKafka): void
    {
        $kernel = self::bootKernel();

        $this->initMockPROVIDERHttpClient($this->exactly(!$isEnabledMonolithKafka));
        $this->initMockFeatureManager($isEnabledMonolithKafka);
        $this->sendMessageToTransport($message, self::EXPORT_ORDER_AMQP_TRANSPORT_NAME);
        $this->executeConsumeCommand($kernel, self::EXPORT_ORDER_AMQP_TRANSPORT_NAME);
    }

    /**
     * @dataProvider exportOrderProvider
     * @param ExportOrder $message
     * @param             $isEnabledMonolithKafka
     * @return void
     */
    public function testKafkaExportOrderHandlingMessage(ExportOrder $message, $isEnabledMonolithKafka): void
    {
        $kernel = self::bootKernel();

        $this->initMockPROVIDERHttpClient($this->exactly($isEnabledMonolithKafka));
        $this->initMockFeatureManager($isEnabledMonolithKafka);
        $this->sendMessageToTransport($message, self::EXPORT_ORDER_KAFKA_TRANSPORT_NAME);
        $this->executeConsumeCommand($kernel, self::EXPORT_ORDER_KAFKA_TRANSPORT_NAME);
    }

    /**
     * @dataProvider syncOrderStatusesProvider
     * @param SyncOrderStatuses $message
     * @param                   $isEnabledMonolithKafka
     * @return void
     */
    public function testAmqpSyncOrderStatusesHandlingMessage(SyncOrderStatuses $message, $isEnabledMonolithKafka): void
    {
        $kernel = self::bootKernel();

        $this->initMockPROVIDERHttpClient($this->exactly($isEnabledMonolithKafka));
        $this->initMockFeatureManager(!$isEnabledMonolithKafka);
        $this->sendMessageToTransport($message, self::SYNC_ORDER_STATUSES_AMQP_TRANSPORT_NAME);
        $this->executeConsumeCommand($kernel, self::SYNC_ORDER_STATUSES_AMQP_TRANSPORT_NAME);
    }

    /**
     * @dataProvider syncOrderStatusesProvider
     * @param SyncOrderStatuses $message
     * @param                   $isEnabledMonolithKafka
     * @return void
     */
    public function testKafkaSyncOrderStatusesHandlingMessage(SyncOrderStatuses $message, $isEnabledMonolithKafka): void
    {
        $kernel = self::bootKernel();

        $this->initMockPROVIDERHttpClient($this->exactly($isEnabledMonolithKafka));
        $this->initMockFeatureManager($isEnabledMonolithKafka);
        $this->sendMessageToTransport($message, self::SYNC_ORDER_STATUSES_KAFKA_TRANSPORT_NAME);
        $this->executeConsumeCommand($kernel, self::SYNC_ORDER_STATUSES_KAFKA_TRANSPORT_NAME);
    }

    /**
     * @throws Exception
     */
    public function testProducingImportRemainMessage(): void
    {
        self::bootKernel();

        $message = new ImportRemain(
            (string)random_int(1000000, 9999999),
            (string)random_int(1000000, 9999999),
        );

        $bus = self::getContainer()->get('messenger.default_bus');
        $bus->dispatch($message);

        /** @var InMemoryTransport $kafkaTransport */
        $kafkaTransport = self::getContainer()->get('messenger.transport.remains_import_kafka');
        /** @var InMemoryTransport $amqpTransport */
        $amqpTransport = self::getContainer()->get('messenger.transport.import_remains');

        self::assertEmpty($kafkaTransport->getSent());
        self::assertNotEmpty($amqpTransport->getSent());
    }

    /**
     * @throws Exception
     */
    public function testProducingImportOrderStatusMessage(): void
    {
        self::bootKernel();

        $message = new ImportOrderStatus(
            (string)random_int(1000000, 9999999),
            OrderStatusInterface::STATUS_CANCELLED,
        );

        $bus = self::getContainer()->get('messenger.default_bus');
        $bus->dispatch($message);

        /** @var InMemoryTransport $kafkaTransport */
        $kafkaTransport = self::getContainer()->get('messenger.transport.orders_import_kafka');
        /** @var InMemoryTransport $amqpTransport */
        $amqpTransport = self::getContainer()->get('messenger.transport.v2_import_orders');

        self::assertEmpty($kafkaTransport->getSent());
        self::assertNotEmpty($amqpTransport->getSent());
    }

    /**
     * @throws Exception
     */
    public function testProducingImportOrderMessage(): void
    {
        self::bootKernel();

        $message = new ImportOrder(
            (string)random_int(1000000, 9999999),
            OrderStatusInterface::STATUS_CANCELLED,
            []
        );

        $bus = self::getContainer()->get('messenger.default_bus');
        $bus->dispatch($message);

        /** @var InMemoryTransport $kafkaTransport */
        $kafkaTransport = self::getContainer()->get('messenger.transport.orders_import_kafka');
        /** @var InMemoryTransport $amqpTransport */
        $amqpTransport = self::getContainer()->get('messenger.transport.v2_import_orders');

        self::assertEmpty($kafkaTransport->getSent());
        self::assertNotEmpty($amqpTransport->getSent());
    }

    /**
     * @throws Exception
     */
    public function testProducingImportOrderReceiptMessage(): void
    {
        self::bootKernel();

        $message = new ImportOrderReceipt(
            (string)random_int(1000000, 9999999),
            2,
            []
        );

        $bus = self::getContainer()->get('messenger.default_bus');
        $bus->dispatch($message);

        /** @var InMemoryTransport $kafkaTransport */
        $kafkaTransport = self::getContainer()->get('messenger.transport.orders_import_kafka');
        /** @var InMemoryTransport $amqpTransport */
        $amqpTransport = self::getContainer()->get('messenger.transport.v2_import_orders');

        self::assertEmpty($kafkaTransport->getSent());
        self::assertNotEmpty($amqpTransport->getSent());
    }

    /**
     * @throws Exception
     */
    public function testProducingChangeRemainQuantityMessage(): void
    {
        self::bootKernel();

        $message = new ChangeRemainQuantity(
            (string)random_int(1000000, 9999999),
            (string)random_int(1000000, 9999999),
            random_int(1, 100),
        );

        $bus = self::getContainer()->get('messenger.default_bus');
        $bus->dispatch($message);

        /** @var InMemoryTransport $kafkaTransport */
        $kafkaTransport = self::getContainer()->get('messenger.transport.remains_change_quantity_kafka');
        /** @var InMemoryTransport $amqpTransport */
        $amqpTransport = self::getContainer()->get('messenger.transport.v2_change_remain_quantity');

        self::assertEmpty($kafkaTransport->getSent());
        self::assertNotEmpty($amqpTransport->getSent());
    }
}