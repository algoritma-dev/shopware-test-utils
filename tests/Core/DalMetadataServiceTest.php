<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Core;

use Algoritma\ShopwareTestUtils\Core\DalMetadataService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Promotion\Aggregate\PromotionPersonaCustomer\PromotionPersonaCustomerDefinition;
use Shopware\Core\Checkout\Promotion\PromotionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;

class DalMetadataServiceTest extends TestCase
{
    private DalMetadataService $service;

    private DefinitionInstanceRegistry&MockObject $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(DefinitionInstanceRegistry::class);
        $this->service = new DalMetadataService($this->registry);
    }

    public function testGetEntityMetadataReturnsNullForNonExistentEntity(): void
    {
        $this->registry->method('getByEntityName')->willThrowException(DataAbstractionLayerException::definitionNotFound('non_existent'));

        $result = $this->service->getEntityMetadata('non_existent');

        $this->assertNull($result);
    }

    public function testGetEntityMetadataReturnsNullForMappingEntity(): void
    {
        /** @phpstan-ignore-next-line */
        $definition = new class() extends MappingEntityDefinition {
            public function __construct() {}

            public function getEntityName(): string
            {
                return 'mapping_entity';
            }

            protected function defineFields(): FieldCollection
            {
                return new FieldCollection();
            }
        };

        $this->registry->method('getByEntityName')->willReturn($definition);

        $result = $this->service->getEntityMetadata('mapping_entity');

        $this->assertNull($result);
    }

    public function testGetEntityMetadataReturnsMetadataForValidEntity(): void
    {
        $definition = $this->createMockDefinition();
        $this->registry->method('getByEntityName')->willReturn($definition);
        $this->registry->method('getByClassOrEntityName')->willReturnOnConsecutiveCalls(
            /** @phpstan-ignore-next-line */
            new PromotionPersonaCustomerDefinition(),
            /** @phpstan-ignore-next-line */
            new PromotionDefinition(),
            /** @phpstan-ignore-next-line */
            new PromotionPersonaCustomerDefinition(),
            /** @phpstan-ignore-next-line */
            new PromotionDefinition()
        );

        $result = $this->service->getEntityMetadata('test_entity');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('entity_name', $result);
        $this->assertArrayHasKey('entity_class', $result);
        $this->assertArrayHasKey('collection_class', $result);
        $this->assertArrayHasKey('definition_class', $result);
        $this->assertArrayHasKey('properties', $result);
        $this->assertCount(4, $result['properties']);
        $this->assertArrayHasKey('relations', $result);
        $this->assertCount(1, $result['relations']);
        $this->assertArrayHasKey('foreign_keys', $result);
        $this->assertCount(1, $result['foreign_keys']);
    }

    public function testGetEntityRelationsReturnsEmptyArrayForNonExistentEntity(): void
    {
        $this->registry->method('getByEntityName')->willThrowException(DataAbstractionLayerException::definitionNotFound('non_existent'));

        $result = $this->service->getEntityRelations('non_existent');

        $this->assertSame([], $result);
    }

    public function testGetEntityRelationsReturnsRelationsForValidEntity(): void
    {
        $definition = $this->createMockDefinition();
        $this->registry->method('getByEntityName')->willReturn($definition);

        $result = $this->service->getEntityRelations('test_entity');

        $this->assertIsArray($result);
    }

    public function testGetEntityPropertiesReturnsEmptyArrayForNonExistentEntity(): void
    {
        $this->registry->method('getByEntityName')->willThrowException(DataAbstractionLayerException::definitionNotFound('non_existent'));

        $result = $this->service->getEntityProperties('non_existent');

        $this->assertSame([], $result);
    }

    public function testGetEntityPropertiesReturnsPropertiesForValidEntity(): void
    {
        $definition = $this->createMockDefinition();
        $this->registry->method('getByEntityName')->willReturn($definition);

        $result = $this->service->getEntityProperties('test_entity');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('name', $result['name']['name']);
        $this->assertSame('string', $result['name']['php_type']);
        $this->assertSame('createdAt', $result['createdAt']['name']);
        $this->assertSame('mixed', $result['createdAt']['php_type']);
        $this->assertSame('updatedAt', $result['updatedAt']['name']);
        $this->assertSame('mixed', $result['updatedAt']['php_type']);
    }

    public function testGetPropertyMethodsReturnsNullForNonExistentEntity(): void
    {
        $this->registry->method('getByEntityName')->willThrowException(DataAbstractionLayerException::definitionNotFound('non_existent'));

        $result = $this->service->getPropertyMethods('non_existent', 'property');

        $this->assertNull($result);
    }

    public function testGetPropertyMethodsReturnsNullForNonExistentProperty(): void
    {
        $definition = $this->createMockDefinition();
        $this->registry->method('getByEntityName')->willReturn($definition);

        $result = $this->service->getPropertyMethods('test_entity', 'non_existent_property');

        $this->assertNull($result);
    }

    public function testGetPropertyMethodsReturnsMethodsForValidProperty(): void
    {
        $definition = $this->createMockDefinition();
        $this->registry->method('getByEntityName')->willReturn($definition);

        $result = $this->service->getPropertyMethods('test_entity', 'name');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('property', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('getter', $result);
        $this->assertArrayHasKey('setter', $result);
        $this->assertSame('name', $result['property']);
        $this->assertSame('field', $result['type']);
        $this->assertSame('getName()', $result['getter']);
        $this->assertSame('setName($name)', $result['setter']);
    }

    public function testGetAssociationPathReturnsNullForNonExistentEntity(): void
    {
        $this->registry->method('getByEntityName')->willThrowException(DataAbstractionLayerException::definitionNotFound('non_existent'));

        $result = $this->service->getAssociationPath('non_existent', 'association');

        $this->assertNull($result);
    }

    public function testGetAssociationPathReturnsNullForNonAssociationField(): void
    {
        $definition = $this->createMockDefinition();
        $this->registry->method('getByEntityName')->willReturn($definition);

        $result = $this->service->getAssociationPath('test_entity', 'name');

        $this->assertNull($result);
    }

    public function testGenerateLoadExampleReturnsEmptyStringForNonExistentEntity(): void
    {
        $this->registry->method('getByEntityName')->willThrowException(DataAbstractionLayerException::definitionNotFound('non_existent'));

        $result = $this->service->generateLoadExample('non_existent', []);

        $this->assertSame('', $result);
    }

    public function testGenerateLoadExampleGeneratesCode(): void
    {
        $definition = $this->createMockDefinition();
        $this->registry->method('getByEntityName')->willReturn($definition);

        $result = $this->service->generateLoadExample('test_entity', []);

        $this->assertStringContainsString('$criteria = new Criteria([$id]);', $result);
        $this->assertStringContainsString('$test_entity = $test_entityRepository->search($criteria, $context)->first();', $result);
    }

    public function testGetAllEntitiesReturnsListOfEntities(): void
    {
        $definition = $this->createMockDefinition();
        $this->registry->method('getDefinitions')->willReturn([$definition]);

        $result = $this->service->getAllEntities();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('class', $result[0]);
        $this->assertArrayHasKey('entity_class', $result[0]);
    }

    private function createMockDefinition(): EntityDefinition
    {
        /** @phpstan-ignore-next-line */
        return new class($this->registry) extends EntityDefinition {
            public function __construct(DefinitionInstanceRegistry $reg)
            {
                $this->registry = $reg;
            }

            public function getEntityName(): string
            {
                return 'test_entity';
            }

            public function getEntityClass(): string
            {
                return Entity::class;
            }

            public function getCollectionClass(): string
            {
                return EntityCollection::class;
            }

            protected function defineFields(): FieldCollection
            {
                $idField = new IdField('id', 'id');
                $nameField = new StringField('name', 'name');
                $fkField = new FkField('category_id', 'categoryId', 'CategoryDefinition');
                $manyToManyField = new ManyToManyAssociationField('promotions', PromotionDefinition::class, PromotionPersonaCustomerDefinition::class, 'customer_id', 'promotion_id');

                return new FieldCollection([$idField, $nameField, $fkField, $manyToManyField]);
            }
        };
    }
}
