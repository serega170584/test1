<?php

declare(strict_types=1);

namespace App\Command\PROVIDER;

use App\Command\AbstractCommand;
use App\Service\MinimalRemain\MinimalRemainManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

class DeleteMinimalRemainCommand extends AbstractCommand
{
    private const NAME = 'app:PROVIDER:minimal-stock:delete';
    private const OPTION_ARTICLE = 'article';

    private MinimalRemainManagerInterface $minimalStockManager;

    public function __construct(
        LoggerInterface               $logger,
        MinimalRemainManagerInterface $minimalStockManager,
    ) {
        parent::__construct($logger);

        $this->minimalStockManager = $minimalStockManager;
    }

    public function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete minimal stock for article')
            ->addOption(
                self::OPTION_ARTICLE,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Article'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $articles = $input->getOption(self::OPTION_ARTICLE);
        if (empty($articles)) {
            throw new UnexpectedValueException('Не указан артикул для удаления');
        }
        $this->minimalStockManager->deleteByArticles($articles);

        return 0;
    }
}
