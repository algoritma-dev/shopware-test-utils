<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Command;

use Algoritma\ShopwareTestUtils\Core\DalMetadataService;
use Algoritma\ShopwareTestUtils\Core\FactoryStubGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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

        $io = new SymfonyStyle($input, $output);

        $io->writeln('Generating factory stubs...');
        try {
            $result = $generator->generate();
            $io->success('Factory stubs generated successfully');
            $output->writeln("  - PHPStan stub: {$result['stub']}");
            $output->writeln("  - PhpStorm meta: {$result['meta']}");
        } catch (\Exception $e) {
            $output->writeln("<error>Error generating stubs: {$e->getMessage()}</error>");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
