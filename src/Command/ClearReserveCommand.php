<?php

namespace App\Command;

use App\Service\OrderReserveManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Очистка устаревших резервов.
 */
class ClearReserveCommand extends AbstractCommand
{
    public const NAME = 'app:reserve:clear';

    private const OPTION_ORDER_ID = 'order-id';

    private OrderReserveManagerInterface $reserveManager;

    public function __construct(LoggerInterface $logger, OrderReserveManagerInterface $reserveManager)
    {
        parent::__construct($logger);
        $this->reserveManager = $reserveManager;
    }

    public function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Clear order reserves')
            ->addOption(
                self::OPTION_ORDER_ID,
                'o',
                InputOption::VALUE_OPTIONAL,
                'Order ID'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Удаление по заказу
        if ($orderId = (int) $input->getOption(self::OPTION_ORDER_ID)) {
            $this->reserveManager->removeOrderReservesByOrderId($orderId);

            return self::SUCCESS;
        }

        $this->reserveManager->removeOutdated();

        return self::SUCCESS;
    }
}
