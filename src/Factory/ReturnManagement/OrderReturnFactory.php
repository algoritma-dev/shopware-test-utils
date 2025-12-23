<?php

namespace Algoritma\ShopwareTestUtils\Factory\ReturnManagement;

use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\ReturnManagement\Entity\OrderReturn\OrderReturnEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderReturnFactory
{
    private array $data;

    private readonly Generator $faker;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'returnNumber' => (string) $this->faker->numberBetween(10000, 99999),
            'requestedAt' => new \DateTime(),
        ];
    }

    public function withOrder(string $orderId): self
    {
        $this->data['orderId'] = $orderId;

        return $this;
    }

    public function withInternalComment(string $comment): self
    {
        $this->data['internalComment'] = $comment;

        return $this;
    }

    public function create(?Context $context = null): OrderReturnEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        /** @var EntityRepository $repository */
        $repository = $this->container->get('order_return.repository');

        $repository->create([$this->data], $context);

        return $repository->search(new Criteria([$this->data['id']]), $context)->first();
    }
}
