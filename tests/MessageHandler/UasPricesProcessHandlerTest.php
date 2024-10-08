<?php

namespace App\Tests\MessageHandler;

use App\Message\PROVIDERPricesProcess;
use App\Tests\Support\Generator\PROVIDERDivisionsGenerator;
use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class PROVIDERPricesProcessHandlerTest extends KernelTestCase
{
    /**
     * @throws Exception
     */
    public function receiveMessageProvider(): array
    {
        $divisions = (new PROVIDERDivisionsGenerator())->generate(2, 10);

        return [
            [
                new PROVIDERPricesProcess($divisions),
            ],
        ];
    }

    /**
     * @dataProvider receiveMessageProvider
     *
     * @throws Exception
     */
    public function testReceiveMessage(PROVIDERPricesProcess $message): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.PROVIDER_prices_process');
        $transport->send(Envelope::wrap($message));

        $command = $application->find('messenger:consume');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'receivers' => ['PROVIDER_prices_process'],
            '--limit' => '1',
        ]);

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.import_remains');
        self::assertCount(20, $transport->getSent());
    }
}
