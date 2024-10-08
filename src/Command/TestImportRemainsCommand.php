<?php

namespace App\Command;

use test1\Message\V1\ImportRemain;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Отправка тестового сообщения обновления остатков.
 */
class TestImportRemainsCommand extends AbstractCommand
{
    public const NAME = 'app:test:import:remains';

    private MessageBusInterface $bus;

    public function __construct(LoggerInterface $logger, MessageBusInterface $bus)
    {
        parent::__construct($logger);
        $this->bus = $bus;
    }

    public function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Send testing update remains message')
            ->addArgument('storeId', InputArgument::REQUIRED, 'Backend STORE_XML_ID')
            ->addArgument('article', InputArgument::REQUIRED)
            ->addArgument('quantity', InputArgument::OPTIONAL)
            ->addArgument('price', InputArgument::OPTIONAL)
            ->addArgument('codeMf', InputArgument::OPTIONAL)
            ->addArgument('vital', InputArgument::OPTIONAL)
            ->addArgument('barcode', InputArgument::OPTIONAL);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $quantity = $input->getArgument('quantity');
        $price = $input->getArgument('price');
        $codeMf = $input->getArgument('codeMf');
        $vital = $input->getArgument('vital');
        $barcode = $input->getArgument('barcode');
        $message = new ImportRemain(
            $input->getArgument('storeId'),
            $input->getArgument('article'),
            $quantity === 'null' ? null : (int) $quantity,
            $price === 'null' ? null : $price,
            $codeMf === 'null' ? null : $codeMf,
            $vital === 'null' ? null : (bool) $vital,
            $barcode === 'null' ? null : $barcode
        );
        $this->bus->dispatch($message, [
            new AmqpStamp('import-remain'), // TODO выпилить после переезда на кафку
        ]);

        return self::SUCCESS;
    }
}
