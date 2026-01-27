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
        if (! is_dir($this->projectRoot)) {
            $output->write("Error generating stubs: Project root {$this->projectRoot} does not exist\n", false, OutputInterface::OUTPUT_RAW);

            return Command::FAILURE;
        }

        $outputDir = $this->projectRoot . '/tests';
        if (! is_dir($outputDir) && (! @mkdir($outputDir, 0o775, true) && ! is_dir($outputDir))) {
            $output->write("Error generating stubs: Failed to create output directory {$outputDir}\n", false, OutputInterface::OUTPUT_RAW);

            return Command::FAILURE;
        }

        $generator = new FactoryStubGenerator($this->projectRoot, $this->metadataService);

        try {
            $result = $generator->generate();

            $stubTarget = $outputDir . '/factory-stubs.php';
            $metaTarget = $outputDir . '/.phpstorm.meta.php';

            if (! @copy($result['stub'], $stubTarget)) {
                throw new \RuntimeException("Failed to copy stub file to {$stubTarget}");
            }
            if (! @copy($result['meta'], $metaTarget)) {
                throw new \RuntimeException("Failed to copy meta file to {$metaTarget}");
            }

            $output->write("Factory stubs generated successfully\n", false, OutputInterface::OUTPUT_RAW);
            $output->write("  - PHPStan stub: {$stubTarget}\n", false, OutputInterface::OUTPUT_RAW);
            $output->write("  - PhpStorm meta: {$metaTarget}\n", false, OutputInterface::OUTPUT_RAW);
        } catch (\Exception $e) {
            $output->write("Error generating stubs: {$e->getMessage()}\n", false, OutputInterface::OUTPUT_RAW);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
