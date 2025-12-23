<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Tax\TaxEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaxFactory
{
    private array $data;

    private readonly Generator $faker;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->word . ' Tax',
            'taxRate' => $this->faker->randomFloat(2, 0, 30),
            'position' => 1,
        ];
    }

    public function withRate(float $rate): self
    {
        $this->data['taxRate'] = $rate;

        return $this;
    }

    public function create(?Context $context = null): TaxEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        /** @var EntityRepository $repository */
        $repository = $this->container->get('tax.repository');

        $repository->create([$this->data], $context);

        return $repository->search(new Criteria([$this->data['id']]), $context)->first();
    }
}
