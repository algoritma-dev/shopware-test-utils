<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Core;

use Shopware\Core\Framework\DataAbstractionLayer\AttributeEntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;

/**
 * Servizio per analizzare i metadati della DAL di Shopware
 * Ricostruisce tutte le proprietà e relazioni di un'entità.
 */
class DalMetadataService
{
    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionRegistry
    ) {}

    /**
     * Ottiene tutti i metadati di un'entità (campi + relazioni)
     * Include automaticamente tutto ciò che è stato aggiunto via Extension.
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

        // Ottieni TUTTI i campi (nativi + da extension)
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
     * Ottiene solo le relazioni di un'entità.
     */
    public function getEntityRelations(string $entityName): array
    {
        $metadata = $this->getEntityMetadata($entityName);

        return $metadata ? $metadata['relations'] : [];
    }

    /**
     * Ottiene solo le proprietà (campi non relazionali).
     */
    public function getEntityProperties(string $entityName): array
    {
        $metadata = $this->getEntityMetadata($entityName);

        return $metadata ? $metadata['properties'] : [];
    }

    /**
     * Genera i metodi getter/setter per una proprietà.
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

        $getter = 'get' . ucfirst($propertyName);
        $setter = 'set' . ucfirst($propertyName);

        if ($field instanceof AssociationField) {
            $referenceDefinition = $field->getReferenceDefinition();
            $referenceEntityClass = $referenceDefinition->getEntityClass();
            $relationType = $this->getRelationType($field);

            if (in_array($relationType, ['OneToMany', 'ManyToMany'])) {
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
        // Campo normale
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
     * Trova il percorso per caricare una relazione con Criteria.
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

        // Per relazioni dirette, il path è semplicemente il nome della proprietà
        return $targetPropertyName;
    }

    /**
     * Genera codice di esempio per caricare un'entità con le sue relazioni.
     */
    public function generateLoadExample(string $entityName, array $associations = []): string
    {
        $definition = $this->findDefinition($entityName);

        if (! $definition instanceof EntityDefinition) {
            return '';
        }

        $repoVar = '$' . $entityName . 'Repository';
        $entityVar = '$' . $entityName;

        $code = "// Carica {$entityName} con associazioni\n";
        $code .= "\$criteria = new Criteria([\$id]);\n";

        foreach ($associations as $association) {
            $code .= "\$criteria->addAssociation('{$association}');\n";
        }

        $code .= "\n{$entityVar} = {$repoVar}->search(\$criteria, \$context)->first();\n";
        $code .= "\n// Accesso alle relazioni\n";

        foreach ($associations as $association) {
            $field = $definition->getFields()->get($association);
            if ($field instanceof AssociationField) {
                $getter = 'get' . ucfirst((string) $association);
                $code .= "{$entityVar}->{$getter}(); // Accesso a {$association}\n";
            }
        }

        return $code;
    }

    /**
     * Lista tutte le entità disponibili.
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

        usort($entities, fn (array $a, array $b): int => $a['name'] <=> $b['name']);

        return $entities;
    }

    // --- Metodi privati di supporto ---

    private function extractProperties(iterable $fields): array
    {
        $properties = [];

        foreach ($fields as $field) {
            if ($field instanceof AssociationField || $field instanceof FkField) {
                continue; // Skip relazioni e FK
            }

            $propertyName = $field->getPropertyName();
            $properties[$propertyName] = [
                'name' => $propertyName,
                'type' => $this->getFieldType($field),
                'php_type' => $this->getPhpType($field),
                //                'storage_name' => $field->getStorageName(),
                //                'flags' => $this->extractFlags($field),
            ];
        }

        return $properties;
    }

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
                'reference_entity' => $referenceDefinition::ENTITY_NAME,
                'reference_class' => $referenceDefinition->getEntityClass(),
                'reference_definition' => $referenceDefinition::class,
                'getter' => 'get' . ucfirst($propertyName),
                'setter' => 'set' . ucfirst($propertyName),
            ];

            // Aggiungi dettagli specifici per tipo
            if ($field instanceof ManyToOneAssociationField) {
                $relationData['foreign_key'] = $field->getStorageName();
                $relationData['reference_field'] = $field->getReferenceField();
            } elseif ($field instanceof OneToManyAssociationField) {
                $relationData['reference_field'] = $field->getReferenceField();
                $relationData['local_field'] = $field->getLocalField();
            } elseif ($field instanceof ManyToManyAssociationField) {
                $mappingDef = $field->getMappingDefinition();
                $relationData['mapping_entity'] = $mappingDef::ENTITY_NAME;
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

        return substr($class, strrpos($class, '\\') + 1);
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
        foreach ($this->definitionRegistry->getDefinitions() as $definition) {
            if ($definition->getEntityName() === $entityName) {
                return $definition;
            }
        }

        // Prova anche con ricerca parziale sul nome della classe
        foreach ($this->definitionRegistry->getDefinitions() as $definition) {
            if (str_contains(strtolower($definition::class), strtolower($entityName))) {
                return $definition;
            }
        }

        return null;
    }
}
