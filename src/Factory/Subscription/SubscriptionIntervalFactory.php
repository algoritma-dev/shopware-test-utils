<?php

namespace Algoritma\ShopwareTestUtils\Factory\Subscription;

use Shopware\Commercial\Subscription\Entity\SubscriptionInterval\SubscriptionIntervalEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\FieldType\CronInterval;
use Shopware\Core\Framework\DataAbstractionLayer\FieldType\DateInterval;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SubscriptionIntervalFactory
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    public function __construct(private readonly ContainerInterface $container) {}

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function withDateInterval(string $interval): self
    {
        $this->data['dateInterval'] = new DateInterval($interval);

        return $this;
    }

    public function withCronInterval(string $cron): self
    {
        $this->data['cronInterval'] = new CronInterval($cron);

        return $this;
    }

    public function create(?Context $context = null): SubscriptionIntervalEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository<SubscriptionIntervalEntity> $repository */
        $repository = $this->container->get('subscription_interval.repository');

        $repository->create([$this->data], $context);

        /** @var SubscriptionIntervalEntity $entity */
        $entity = $repository->search(new Criteria([$this->data['id']]), $context)->first();

        return $entity;
    }
}
