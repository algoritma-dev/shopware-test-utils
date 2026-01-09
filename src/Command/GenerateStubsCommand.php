<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Command;

use Algoritma\ShopwareTestUtils\Core\DalMetadataService;
use Algoritma\ShopwareTestUtils\Core\FactoryStubGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'alg:sw-test-util:generate-stubs',
    description: 'Generates PHPStan and PhpStorm stub files for factory classes'
)]
class GenerateStubsCommand extends Command
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly DalMetadataService $metadataService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->metadataService);

        echo "Generating factory stubs...\n";

        try {
            $result = $generator->generate();
            echo "✓ Factory stubs generated successfully:\n";
            echo "  - PHPStan stub: {$result['stub']}\n";
            echo "  - PhpStorm meta: {$result['meta']}\n";
            echo "\nTo use with PHPStan (already configured):\n";
            echo "    stubFiles:\n";
            echo "        - var/cache/factory-stubs.php\n";
            echo "\nTo use with PhpStorm:\n";
            echo "    Copy var/cache/.phpstorm.meta.php to your project root\n";
            echo "    Or merge its contents into your existing .phpstorm.meta.php\n";
        } catch (\Exception $e) {
            fwrite(STDERR, "✗ Error generating stubs: {$e->getMessage()}\n");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
