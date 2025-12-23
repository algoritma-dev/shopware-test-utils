<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaFactory
{
    private array $data;

    private readonly Generator $faker;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'private' => false,
            'mediaFolderId' => null, // Optional: could fetch a default folder
            'alt' => $this->faker->sentence,
            'title' => $this->faker->word,
        ];
    }

    public function withTitle(string $title): self
    {
        $this->data['title'] = $title;

        return $this;
    }

    public function create(?Context $context = null): MediaEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository $repository */
        $repository = $this->container->get('media.repository');

        $repository->create([$this->data], $context);

        return $repository->search(new Criteria([$this->data['id']]), $context)->first();
    }
}
