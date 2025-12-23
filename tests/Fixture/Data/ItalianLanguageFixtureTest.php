<?php

namespace Shopware\Commercial\Tests\Fixture\Data;

use Algoritma\ShopwareTestUtils\Factory\LanguageFactory;
use Algoritma\ShopwareTestUtils\Fixture\Data\ItalianLanguageFixture;
use Algoritma\ShopwareTestUtils\Fixture\ReferenceRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
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
        $contextMock = $this->createMock(Context::class);
        $languageFactoryMock = $this->createMock(LanguageFactory::class);
        $languageMock = $this->createMock(LanguageEntity::class);

        $this->containerMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['locale.repository', $localeRepoMock],
                ['context', $contextMock],
            ]);

        $localeRepoMock->expects($this->once())
            ->method('searchIds')
            ->with($this->callback(function (Criteria $criteria): bool {
                $filters = $criteria->getFilters();

                return count($filters) === 1 && $filters[0] instanceof EqualsFilter && $filters[0]->getField() === 'code' && $filters[0]->getValue() === 'it-IT';
            }), $contextMock)
            ->willReturn(new class() {
                public function firstId(): string
                {
                    return 'locale-id';
                }
            });

        $languageFactoryMock
            ->expects($this->once())
            ->method('withName')
            ->with('Italiano');
        $languageFactoryMock
            ->expects($this->once())
            ->method('withLocale')
            ->with('locale-id');
        $languageFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($languageMock);

        $this->containerMock
            ->expects($this->once())
            ->method('get')
            ->with(LanguageFactory::class)
            ->willReturn($languageFactoryMock);

        $this->fixture->load($this->references);

        $this->assertTrue($this->references->has('italian-language'));
        $this->assertSame($languageMock, $this->references->get('italian-language'));
    }
}
