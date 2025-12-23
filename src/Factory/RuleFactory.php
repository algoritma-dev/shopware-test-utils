<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RuleFactory
{
    private array $data;

    private readonly Generator $faker;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(3, true),
            'priority' => $this->faker->numberBetween(1, 100),
            'conditions' => [
                [
                    'type' => 'alwaysValid', // A simple condition that is always true
                ],
            ],
        ];
    }

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function withPriority(int $priority): self
    {
        $this->data['priority'] = $priority;

        return $this;
    }

    public function create(?Context $context = null): RuleEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository $repository */
        $repository = $this->container->get('rule.repository');

        $repository->create([$this->data], $context);

        return $repository->search(new Criteria([$this->data['id']]), $context)->first();
    }
}
