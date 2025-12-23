<?php

namespace Algoritma\ShopwareTestUtils\Fixture\Data;

use Algoritma\ShopwareTestUtils\Factory\LanguageFactory;
use Algoritma\ShopwareTestUtils\Fixture\AbstractFixture;
use Algoritma\ShopwareTestUtils\Fixture\ReferenceRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ItalianLanguageFixture extends AbstractFixture
{
    public function load(ReferenceRepository $references): void
    {
        $localeId = $this->container->get('locale.repository')
            ?->searchIds(
                (new Criteria())
                    ->addFilter(
                        new EqualsFilter('code', 'it-IT')
                    ),
                $this->container->get('context')?->getContext()
            )
            ->firstId();

        $factory = new LanguageFactory($this->getContainer());
        $factory->withName('Italiano');
        $factory->withLocale($localeId);
        $language = $factory->create();

        $references->set('italian-language', $language);
    }
}
