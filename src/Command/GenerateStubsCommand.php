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

        try {
            $result = $generator->generate();
            $output->write("Factory stubs generated successfully\n", false, OutputInterface::OUTPUT_RAW);
            $output->write("  - PHPStan stub: {$result['stub']}\n", false, OutputInterface::OUTPUT_RAW);
            $output->write("  - PhpStorm meta: {$result['meta']}\n", false, OutputInterface::OUTPUT_RAW);
        } catch (\Exception $e) {
            $output->write("Error generating stubs: {$e->getMessage()}\n", false, OutputInterface::OUTPUT_RAW);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
