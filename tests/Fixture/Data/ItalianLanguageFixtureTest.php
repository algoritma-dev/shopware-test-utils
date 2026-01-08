<?php

namespace Algoritma\ShopwareTestUtils\Tests\Fixture\Data;

use Algoritma\ShopwareTestUtils\Fixture\Data\ItalianLanguageFixture;
use Algoritma\ShopwareTestUtils\Fixture\ReferenceRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ItalianLanguageFixtureTest extends TestCase
{
    private ItalianLanguageFixture $fixture;

    private ReferenceRepository $references;

    private MockObject $containerMock;

    protected function setUp(): void
    {
        $this->references = new ReferenceRepository();
        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->fixture = new ItalianLanguageFixture();
        $this->fixture->setContainer($this->containerMock);
    }

    public function testLoadAddsLanguageToReferences(): void
    {
        $localeRepoMock = $this->createMock(EntityRepository::class);
        $languageRepoMock = $this->createMock(EntityRepository::class);

        $contextServiceMock = new class() {
            public function getContext(): Context
            {
                return Context::createCLIContext();
            }
        };

        $this->containerMock
            ->method('get')
            ->willReturnCallback(fn (string $id) => match ($id) {
                'locale.repository' => $localeRepoMock,
                'language.repository' => $languageRepoMock,
                'context' => $contextServiceMock,
                default => null,
            });

        $localeId = Uuid::randomHex();
        $localeIdMock = $this->createMock(IdSearchResult::class);
        $localeIdMock->method('firstId')->willReturn($localeId);

        $localeRepoMock->expects($this->once())
            ->method('searchIds')
            ->with($this->callback(function (Criteria $criteria): bool {
                $filters = $criteria->getFilters();

                return count($filters) === 1
                    && $filters[0] instanceof EqualsFilter
                    && $filters[0]->getField() === 'code'
                    && $filters[0]->getValue() === 'it-IT';
            }))
            ->willReturn($localeIdMock);

        $languageRepoMock->expects($this->once())
            ->method('create');

        $languageRepoMock->method('search')->willReturnCallback(function (): MockObject {
            $result = $this->createMock(EntitySearchResult::class);
            $entity = new LanguageEntity();
            $entity->setId('new-lang-id');
            $entity->setName('Italiano');
            $result->method('first')->willReturn($entity);

            return $result;
        });

        $this->fixture->load($this->references);

        $this->assertTrue($this->references->has('italian-language'));
        $this->assertInstanceOf(LanguageEntity::class, $this->references->get('italian-language'));
    }
}
