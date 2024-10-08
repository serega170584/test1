<?php

declare(strict_types=1);

namespace App\Command\PROVIDER;

use App\Command\AbstractCommand;
use App\Dto\SaveMinimalRemainDto;
use App\Service\MinimalRemain\MinimalRemainManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddMinimalRemainCommand extends AbstractCommand
{
    private const NAME = 'app:PROVIDER:minimal-stock:add';
    private const OPTION_ARTICLE = 'article';
    private const OPTION_QUANTITY = 'quantity';

    private MinimalRemainManagerInterface $minimalStockManager;

    public function __construct(
        MinimalRemainManagerInterface $minimalStockManager,
        LoggerInterface $logger,
    ) {
        parent::__construct($logger);

        $this->minimalStockManager = $minimalStockManager;
    }

    public function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Add minimal remain for article')
            ->addOption(
                self::OPTION_ARTICLE,
                null,
                InputOption::VALUE_REQUIRED,
                'Article'
            )
            ->addOption(
                self::OPTION_QUANTITY,
                null,
                InputOption::VALUE_REQUIRED,
                'Minimal remain quantity'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $article = $input->getOption(self::OPTION_ARTICLE);
        $minimalRemainQuantity = (int) $input->getOption(self::OPTION_QUANTITY);

        $dto = (new SaveMinimalRemainDto())
            ->setArticle($article)
            ->setMinimalRemainQuantity($minimalRemainQuantity);
        $this->minimalStockManager->saveMinimalRemain($dto);

        return 0;
    }
}
