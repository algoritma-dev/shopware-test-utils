<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Core;

use Shopware\Core\Framework\DataAbstractionLayer\AttributeEntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\DefinitionNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;

/**
 * Service to analyze Shopware DAL metadata
 * Reconstructs all properties and relations of an entity.
 */
class DalMetadataService
{
    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionRegistry
    ) {}

    /**
     * Gets all metadata of an entity (fields + relations)
     * Automatically includes everything that has been added via Extension.
     *
     * @return array{
     *     entity_name: string,
     *     entity_class: class-string,
     *     collection_class: class-string,
     *     definition_class: class-string,
     *     properties: array<string, array{name: string, type: string, php_type: string}>,
     *     relations: array<string, array<string, mixed>>,
     *     foreign_keys: array<string, array{name: string, storage_name: string, reference_definition: EntityDefinition}>
     * }|null
     */
    public function getEntityMetadata(string $entityName): ?array
    {
        $definition = $this->findDefinition($entityName);

        if (! $definition instanceof EntityDefinition) {
            return null;
        }

        if ($definition instanceof MappingEntityDefinition) {
            return null;
        }

        // Get ALL fields (native + from extension)
        $fields = $definition->getFields();

        return [
            'entity_name' => $definition->getEntityName(),
            'entity_class' => $definition->getEntityClass(),
            'collection_class' => $definition->getCollectionClass(),
            'definition_class' => $definition::class,
            'properties' => $this->extractProperties($fields),
            'relations' => $this->extractRelations($fields),
            'foreign_keys' => $this->extractForeignKeys($fields),
        ];
    }

    /**
     * Gets only the relations of an entity.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getEntityRelations(string $entityName): array
    {
        $metadata = $this->getEntityMetadata($entityName);

        return $metadata ? $metadata['relations'] : [];
    }

    /**
     * Gets only the properties (non-relational fields).
     *
     * @return array<string, array{name: string, type: string, php_type: string}>
     */
    public function getEntityProperties(string $entityName): array
    {
        $metadata = $this->getEntityMetadata($entityName);

        return $metadata ? $metadata['properties'] : [];
    }

    /**
     * Generates getter/setter methods for a property.
     *
     * @return array<string, string>|null
     */
    public function getPropertyMethods(string $entityName, string $propertyName): ?array
    {
        $definition = $this->findDefinition($entityName);

        if (! $definition instanceof EntityDefinition) {
            return null;
        }

        $field = $definition->getFields()->get($propertyName);

        if (! $field instanceof Field) {
            return null;
        }

        $getter = 'get' . \ucfirst($propertyName);
        $setter = 'set' . \ucfirst($propertyName);

        if ($field instanceof AssociationField) {
            $referenceDefinition = $field->getReferenceDefinition();
            $referenceEntityClass = $referenceDefinition->getEntityClass();
            $relationType = $this->getRelationType($field);

            if (\in_array($relationType, ['OneToMany', 'ManyToMany'])) {
                // Collection
                $collectionClass = $referenceDefinition->getCollectionClass();

                return [
                    'property' => $propertyName,
                    'type' => 'collection',
                    'relation_type' => $relationType,
                    'getter' => $getter . '()',
                    'setter' => $setter . '($' . $propertyName . ')',
                    'getter_signature' => "public function {$getter}(): ?{$collectionClass}",
                    'setter_signature' => "public function {$setter}({$collectionClass} \${$propertyName}): void",
                    'entity_class' => $referenceEntityClass,
                    'collection_class' => $collectionClass,
                ];
            }

            // Single entity
            return [
                'property' => $propertyName,
                'type' => 'entity',
                'relation_type' => $relationType,
                'getter' => $getter . '()',
                'setter' => $setter . '($' . $propertyName . ')',
                'getter_signature' => "public function {$getter}(): ?{$referenceEntityClass}",
                'setter_signature' => "public function {$setter}(?{$referenceEntityClass} \${$propertyName}): void",
                'entity_class' => $referenceEntityClass,
            ];
        }
        // Normal field
        $phpType = $this->getPhpType($field);

        return [
            'property' => $propertyName,
            'type' => 'field',
            'getter' => $getter . '()',
            'setter' => $setter . '($' . $propertyName . ')',
            'getter_signature' => "public function {$getter}(): ?{$phpType}",
            'setter_signature' => "public function {$setter}(?{$phpType} \${$propertyName}): void",
            'php_type' => $phpType,
        ];
    }

    /**
     * Finds the path to load a relation with Criteria.
     */
    public function getAssociationPath(string $entityName, string $targetPropertyName): ?string
    {
        $definition = $this->findDefinition($entityName);

        if (! $definition instanceof EntityDefinition) {
            return null;
        }

        $field = $definition->getFields()->get($targetPropertyName);

        if (! $field instanceof Field || ! $field instanceof AssociationField) {
            return null;
        }

        return $targetPropertyName;
    }

    /**
     * Generates example code to load an entity with its relations.
     *
     * @param list<string> $associations
     */
    public function generateLoadExample(string $entityName, array $associations = []): string
    {
        $definition = $this->findDefinition($entityName);

        if (! $definition instanceof EntityDefinition) {
            return '';
        }

        $repoVar = '$' . $entityName . 'Repository';
        $entityVar = '$' . $entityName;

        $code = "// Load {$entityName} with associations\n";
        $code .= "\$criteria = new Criteria([\$id]);\n";

        foreach ($associations as $association) {
            $code .= "\$criteria->addAssociation('{$association}');\n";
        }

        $code .= "\n{$entityVar} = {$repoVar}->search(\$criteria, \$context)->first();\n";
        $code .= "\n// Access relations\n";

        foreach ($associations as $association) {
            $field = $definition->getFields()->get($association);
            if ($field instanceof AssociationField) {
                $getter = 'get' . \ucfirst((string) $association);
                $code .= "{$entityVar}->{$getter}(); // Access {$association}\n";
            }
        }

        return $code;
    }

    /**
     * Lists all available entities.
     *
     * @return list<array{name: string, class: class-string, entity_class: class-string}>
     */
    public function getAllEntities(): array
    {
        $entities = [];
        foreach ($this->definitionRegistry->getDefinitions() as $definition) {
            $entities[] = [
                'name' => $definition->getEntityName(),
                'class' => $definition::class,
                'entity_class' => $definition->getEntityClass(),
            ];
        }

        \usort($entities, fn (array $a, array $b): int => $a['name'] <=> $b['name']);

        return $entities;
    }

    /**
     * @param iterable<Field> $fields
     *
     * @return array<string, array{name: string, type: string, php_type: string}>
     */
    private function extractProperties(iterable $fields): array
    {
        $properties = [];

        foreach ($fields as $field) {
            if ($field instanceof AssociationField || $field instanceof FkField) {
                continue; // Skip relations and FKs
            }

            $propertyName = $field->getPropertyName();
            $properties[$propertyName] = [
                'name' => $propertyName,
                'type' => $this->getFieldType($field),
                'php_type' => $this->getPhpType($field),
            ];
        }

        return $properties;
    }

    /**
     * @param iterable<Field> $fields
     *
     * @return array<string, array<string, mixed>>
     */
    private function extractRelations(iterable $fields): array
    {
        $relations = [];

        foreach ($fields as $field) {
            if (! $field instanceof AssociationField) {
                continue;
            }

            $propertyName = $field->getPropertyName();
            $referenceDefinition = $field->getReferenceDefinition();
            $relationType = $this->getRelationType($field);

            if ($referenceDefinition instanceof MappingEntityDefinition) {
                continue;
            }

            if ($referenceDefinition instanceof AttributeEntityDefinition) {
                continue;
            }

            $relationData = [
                'name' => $propertyName,
                'type' => $relationType,
                'reference_entity' => $referenceDefinition->getEntityName(),
                'reference_class' => $referenceDefinition->getEntityClass(),
                'reference_definition' => $referenceDefinition::class,
                'getter' => 'get' . \ucfirst($propertyName),
                'setter' => 'set' . \ucfirst($propertyName),
            ];

            if ($field instanceof ManyToOneAssociationField) {
                $relationData['foreign_key'] = $field->getStorageName();
                $relationData['reference_field'] = $field->getReferenceField();
            } elseif ($field instanceof OneToManyAssociationField) {
                $relationData['reference_field'] = $field->getReferenceField();
                $relationData['local_field'] = $field->getLocalField();
            } elseif ($field instanceof ManyToManyAssociationField) {
                $mappingDef = $field->getMappingDefinition();
                $relationData['mapping_entity'] = $mappingDef->getEntityName();
                $relationData['mapping_definition'] = $mappingDef::class;
                $relationData['mapping_local_column'] = $field->getMappingLocalColumn();
                $relationData['mapping_reference_column'] = $field->getMappingReferenceColumn();
            } elseif ($field instanceof OneToOneAssociationField) {
                $relationData['foreign_key'] = $field->getStorageName();
                $relationData['reference_field'] = $field->getReferenceField();
            }

            $relations[$propertyName] = $relationData;
        }

        return $relations;
    }

    /**
     * @param iterable<Field> $fields
     *
     * @return array<string, array{name: string, storage_name: string, reference_definition: EntityDefinition}>
     */
    private function extractForeignKeys(iterable $fields): array
    {
        $fks = [];

        foreach ($fields as $field) {
            if ($field instanceof FkField) {
                $fks[$field->getPropertyName()] = [
                    'name' => $field->getPropertyName(),
                    'storage_name' => $field->getStorageName(),
                    'reference_definition' => $field->getReferenceDefinition(),
                ];
            }
        }

        return $fks;
    }

    private function getRelationType(AssociationField $field): string
    {
        return match (true) {
            $field instanceof ManyToOneAssociationField => 'ManyToOne',
            $field instanceof OneToManyAssociationField => 'OneToMany',
            $field instanceof OneToOneAssociationField => 'OneToOne',
            $field instanceof ManyToManyAssociationField => 'ManyToMany',
            default => 'Unknown',
        };
    }

    private function getFieldType(Field $field): string
    {
        $class = $field::class;

        return \substr($class, \strrpos($class, '\\') + 1);
    }

    private function getPhpType(Field $field): string
    {
        $fieldType = $this->getFieldType($field);

        return match ($fieldType) {
            'IdField', 'FkField' => 'string',
            'StringField', 'LongTextField', 'TextField' => 'string',
            'IntField' => 'int',
            'FloatField' => 'float',
            'BoolField' => 'bool',
            'DateField', 'DateTimeField' => '\DateTimeInterface',
            'JsonField' => 'array',
            'PriceField' => 'float',
            default => 'mixed',
        };
    }

    private function findDefinition(string $entityName): ?EntityDefinition
    {
        try {
            return $this->definitionRegistry->getByEntityName($entityName);
        } catch (DefinitionNotFoundException) {
            return null;
        }
    }
}
