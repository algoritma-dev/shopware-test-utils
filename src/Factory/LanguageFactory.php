<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LanguageFactory
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->data = [
            'id' => Uuid::randomHex(),
            'active' => true,
        ];
    }

    public function withName(string $name): void
    {
        $this->data['name'] = $name;
    }

    public function withLocale(string $localeId): void
    {
        $this->data['localeId'] = $localeId;
    }

    public function create(?Context $context = null): LanguageEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository<LanguageEntity> $repository */
        $repository = $this->container->get('language.repository');

        $repository->create([$this->data], $context);

        /** @var LanguageEntity $entity */
        $entity = $repository->search(new Criteria([$this->data['id']]), $context)->first();

        return $entity;
    }
}
