<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LanguageFactory extends AbstractFactory
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->data = [
            'id' => Uuid::randomHex(),
            'active' => true,
        ];
    }

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function withLocale(string $localeId): self
    {
        $this->data['localeId'] = $localeId;

        return $this;
    }

    protected function getRepositoryName(): string
    {
        return 'language.repository';
    }

    protected function getEntityName(): string
    {
        return LanguageDefinition::ENTITY_NAME;
    }
}
