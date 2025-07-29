<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SimpleThings\EntityAudit\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Name\Identifier;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;
use SimpleThings\EntityAudit\Utils\DbalCompatibilityTrait;
use SimpleThings\EntityAudit\Utils\ORMCompatibilityTrait;

/**
 * NEXT_MAJOR: do not implement EventSubscriber interface anymore.
 * NEXT_MAJOR: Declare the class as final.
 *
 * @final since 1.19.0
 */
class CreateSchemaListener implements EventSubscriber
{
    use DbalCompatibilityTrait;
    use ORMCompatibilityTrait;

    private AuditConfiguration $config;

    private MetadataFactory $metadataFactory;

    /**
     * @var string[]
     */
    private array $defferedJoinTablesToCreate = [];

    public function __construct(AuditManager $auditManager)
    {
        $this->config = $auditManager->getConfiguration();
        $this->metadataFactory = $auditManager->getMetadataFactory();
    }

    /**
     * NEXT_MAJOR: remove this method.
     *
     * @return string[]
     */
    #[\ReturnTypeWillChange]
    public function getSubscribedEvents()
    {
        return [
            ToolEvents::postGenerateSchemaTable,
            ToolEvents::postGenerateSchema,
        ];
    }

    /**
     * @psalm-suppress TypeDoesNotContainType, NoValue
     */
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $eventArgs): void
    {
        $cm = $eventArgs->getClassMetadata();

        if (!$this->metadataFactory->isAudited($cm->name)) {
            $audited = false;
            if ($cm->isInheritanceTypeJoined() && $cm->rootEntityName === $cm->name) {
                foreach ($cm->subClasses as $subClass) {
                    if ($this->metadataFactory->isAudited($subClass)) {
                        $audited = true;

                        break;
                    }
                }
            }
            if (!$audited) {
                return;
            }
        }

        $schema = $eventArgs->getSchema();

        $revisionsTable = $this->createRevisionsTable($schema);

        $entityTable = $eventArgs->getClassTable();
        if ($this->isDbal4_3()) {
            $tableName = $this->config->getTablePrefix().$entityTable->getObjectName()->toString().$this->config->getTableSuffix();
        } else {
            /** @psalm-suppress InternalMethod */
            $tableName = $this->config->getTablePrefix().$entityTable->getName().$this->config->getTableSuffix(); // @phpstan-ignore-line
        }
        $revisionTable = $schema->createTable($tableName);

        foreach ($entityTable->getColumns() as $column) {
            $this->addColumnToTable($column, $revisionTable);
        }
        $revisionTable->addColumn($this->config->getRevisionFieldName(), $this->config->getRevisionIdFieldType());
        $revisionTable->addColumn($this->config->getRevisionTypeFieldName(), Types::STRING, ['length' => 4]);
        if (!\in_array($cm->inheritanceType, [ClassMetadata::INHERITANCE_TYPE_NONE, ClassMetadata::INHERITANCE_TYPE_JOINED, ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE], true)) {
            throw new \RuntimeException(\sprintf('Inheritance type "%s" is not yet supported', $cm->inheritanceType));
        }

        if ($this->isDbal4_3()) {
            $primaryKey = $entityTable->getPrimaryKeyConstraint();
            \assert(null !== $primaryKey);
            $pkColumns = $primaryKey->getColumnNames();
            /** @psalm-suppress ArgumentTypeCoercion */
            $pkColumns[] = new UnqualifiedName(Identifier::unquoted($this->config->getRevisionFieldName())); // @phpstan-ignore-line
            $editor = PrimaryKeyConstraint::editor()->setColumnNames(...$pkColumns)->setIsClustered($primaryKey->isClustered());
            $revisionTable->addPrimaryKeyConstraint($editor->create());
        } else {
            /** @psalm-suppress DeprecatedMethod */
            $primaryKey = $entityTable->getPrimaryKey();
            \assert(null !== $primaryKey);
            /** @psalm-suppress DeprecatedMethod */
            $pkColumns = $primaryKey->getColumns();
            $pkColumns[] = $this->config->getRevisionFieldName();
            /** @psalm-suppress DeprecatedMethod */
            $revisionTable->setPrimaryKey($pkColumns);
        }
        if ($this->isDbal4_3()) {
            $revIndexName = $this->config->getRevisionFieldName().'_'.md5($revisionTable->getObjectName()->toString()).'_idx';
        } else {
            /** @psalm-suppress InternalMethod */
            $revIndexName = $this->config->getRevisionFieldName().'_'.md5($revisionTable->getName()).'_idx'; // @phpstan-ignore-line
        }
        $revisionTable->addIndex([$this->config->getRevisionFieldName()], $revIndexName);

        foreach ($cm->associationMappings as $associationMapping) {
            if (self::isManyToManyOwningSideMapping($associationMapping)) {
                if ($schema->hasTable(self::getMappingJoinTableNameValue($associationMapping))) {
                    $this->createRevisionJoinTableForJoinTable($schema, self::getMappingJoinTableNameValue($associationMapping));
                } else {
                    $this->defferedJoinTablesToCreate[] = self::getMappingJoinTableNameValue($associationMapping);
                }
            }
        }

        if (!$this->config->areForeignKeysDisabled()) {
            $this->createForeignKeys($revisionTable, $revisionsTable);
        }
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs): void
    {
        $schema = $eventArgs->getSchema();
        $this->createRevisionsTable($schema);

        foreach ($this->defferedJoinTablesToCreate as $defferedJoinTableToCreate) {
            $this->createRevisionJoinTableForJoinTable($schema, $defferedJoinTableToCreate);
        }
    }

    private function createForeignKeys(Table $relatedTable, Table $revisionsTable): void
    {
        if ($this->isDbal4_3()) {
            $revisionForeignKeyName = $this->config->getRevisionFieldName().'_'.md5($relatedTable->getObjectName()->toString()).'_fk';
            $primaryKey = $revisionsTable->getPrimaryKeyConstraint();
            \assert(null !== $primaryKey);
            $relatedTable->addForeignKeyConstraint(
                $revisionsTable->getObjectName()->toString(),
                [$this->config->getRevisionFieldName()],
                array_map(static fn (UnqualifiedName $name) => $name->toString(), $primaryKey->getColumnNames()),
                [],
                $revisionForeignKeyName
            );
        } elseif ($this->isDbal4()) {
            /** @psalm-suppress InternalMethod */
            $revisionForeignKeyName = $this->config->getRevisionFieldName().'_'.md5($relatedTable->getName()).'_fk'; // @phpstan-ignore-line
            /** @psalm-suppress DeprecatedMethod */
            $primaryKey = $revisionsTable->getPrimaryKey();
            \assert(null !== $primaryKey);
            /** @psalm-suppress InternalMethod */
            /** @psalm-suppress DeprecatedMethod */
            $relatedTable->addForeignKeyConstraint(
                $revisionsTable->getName(), // @phpstan-ignore-line
                [$this->config->getRevisionFieldName()],
                $primaryKey->getColumns(),
                [],
                $revisionForeignKeyName
            );
        } else {
            /** @psalm-suppress InternalMethod */
            $revisionForeignKeyName = $this->config->getRevisionFieldName().'_'.md5($relatedTable->getName()).'_fk'; // @phpstan-ignore-line
            /** @psalm-suppress DeprecatedMethod */
            $primaryKey = $revisionsTable->getPrimaryKey();
            \assert(null !== $primaryKey);
            /** @psalm-suppress DeprecatedMethod */
            /** @psalm-suppress InvalidArgument */
            $relatedTable->addForeignKeyConstraint(
                $revisionsTable, // @phpstan-ignore-line doctrine/dbal 3 support for old addForeignKeyConstraint() signature
                [$this->config->getRevisionFieldName()],
                $primaryKey->getColumns(),
                [],
                $revisionForeignKeyName
            );
        }
    }

    /**
     * Copies $column to another table. All its options are copied but notnull and autoincrement which are set to false.
     */
    private function addColumnToTable(Column $column, Table $targetTable): void
    {
        if ($this->isDbal4_3()) {
            $columnName = $column->getObjectName()->toString();
        } else {
            /** @psalm-suppress InternalMethod */
            $columnName = $column->getName(); // @phpstan-ignore-line
        }

        $targetTable->addColumn(
            $columnName,
            Type::getTypeRegistry()->lookupName($column->getType())
        );

        $targetColumn = $targetTable->getColumn($columnName);
        $targetColumn->setLength($column->getLength());
        $targetColumn->setPrecision($column->getPrecision());
        $targetColumn->setScale($column->getScale());
        $targetColumn->setUnsigned($column->getUnsigned());
        $targetColumn->setFixed($column->getFixed());
        $targetColumn->setDefault($column->getDefault());
        if ('' !== $column->getColumnDefinition()) {
            $targetColumn->setColumnDefinition($column->getColumnDefinition());
        }
        $targetColumn->setComment($column->getComment());
        if ($this->isDbal4_3()) {
            $targetColumn->setPlatformOptions(['charset' => $column->getCharset(), 'collation' => $column->getCollation()]);
        } else {
            /** @psalm-suppress DeprecatedMethod */
            $targetColumn->setPlatformOptions($column->getPlatformOptions());
        }

        $targetColumn->setNotnull(false);
        $targetColumn->setAutoincrement(false);
    }

    private function createRevisionsTable(Schema $schema): Table
    {
        $revisionsTableName = $this->config->getRevisionTableName();

        if ($schema->hasTable($revisionsTableName)) {
            return $schema->getTable($revisionsTableName);
        }

        $revisionsTable = $schema->createTable($revisionsTableName);
        $revisionsTable->addColumn('id', $this->config->getRevisionIdFieldType(), [
            'autoincrement' => true,
        ]);
        $revisionsTable->addColumn('timestamp', Types::DATETIME_MUTABLE);
        $revisionsTable->addColumn('username', Types::STRING, ['length' => 255])->setNotnull(false);
        if ($this->isDbal4_3()) {
            $id = new UnqualifiedName(Identifier::unquoted('id'));
            $editor = PrimaryKeyConstraint::editor()->setColumnNames($id)->setIsClustered(true);
            $revisionsTable->addPrimaryKeyConstraint($editor->create());
        } else {
            /** @psalm-suppress DeprecatedMethod */
            $revisionsTable->setPrimaryKey(['id']);
        }

        return $revisionsTable;
    }

    private function createRevisionJoinTableForJoinTable(Schema $schema, string $joinTableName): void
    {
        $joinTable = $schema->getTable($joinTableName);
        if ($this->isDbal4_3()) {
            $revisionJoinTableName = $this->config->getTablePrefix().$joinTable->getObjectName()->toString().$this->config->getTableSuffix();
        } else {
            /** @psalm-suppress InternalMethod */
            $revisionJoinTableName = $this->config->getTablePrefix().$joinTable->getName().$this->config->getTableSuffix(); // @phpstan-ignore-line
        }

        if ($schema->hasTable($revisionJoinTableName)) {
            return;
        }

        $typeRegistry = Type::getTypeRegistry();
        if ($this->isDbal4_3()) {
            $tableName = $this->config->getTablePrefix().$joinTable->getObjectName()->toString().$this->config->getTableSuffix();
        } else {
            /** @psalm-suppress InternalMethod */
            $tableName = $this->config->getTablePrefix().$joinTable->getName().$this->config->getTableSuffix(); // @phpstan-ignore-line
        }
        $revisionJoinTable = $schema->createTable($tableName);
        /** @var Column $column */
        foreach ($joinTable->getColumns() as $column) {
            $options = ['notnull' => false, 'autoincrement' => false];
            if ($column->getType() instanceof StringType) {
                $options['length'] = $column->getLength();
            }
            if ($this->isDbal4_3()) {
                $revisionJoinTable->addColumn($column->getObjectName()->toString(), $typeRegistry->lookupName($column->getType()), $options);
            } else {
                /** @psalm-suppress InternalMethod */
                $revisionJoinTable->addColumn($column->getName(), $typeRegistry->lookupName($column->getType()), $options); // @phpstan-ignore-line
            }
        }
        $revisionJoinTable->addColumn($this->config->getRevisionFieldName(), $this->config->getRevisionIdFieldType());
        $revisionJoinTable->addColumn($this->config->getRevisionTypeFieldName(), 'string', ['length' => 4]);

        if ($this->isDbal4_3()) {
            $pk = $joinTable->getPrimaryKeyConstraint();
            $pkColumns = null !== $pk ? $pk->getColumnNames() : [];
            /** @psalm-suppress ArgumentTypeCoercion */
            $pkColumns[] = new UnqualifiedName(Identifier::unquoted($this->config->getRevisionFieldName())); // @phpstan-ignore-line
            $editor = PrimaryKeyConstraint::editor()->setColumnNames(...$pkColumns)->setIsClustered(!(null !== $pk) || $pk->isClustered());
            $revisionJoinTable->addPrimaryKeyConstraint($editor->create());
        } else {
            /** @psalm-suppress DeprecatedMethod */
            $pk = $joinTable->getPrimaryKey();
            /** @psalm-suppress DeprecatedMethod */
            $pkColumns = null !== $pk ? $pk->getColumns() : [];
            $pkColumns[] = $this->config->getRevisionFieldName();
            /** @psalm-suppress DeprecatedMethod */
            $revisionJoinTable->setPrimaryKey($pkColumns);
        }
        if ($this->isDbal4_3()) {
            $revIndexName = $this->config->getRevisionFieldName().'_'.md5($revisionJoinTable->getObjectName()->toString()).'_idx';
        } else {
            /** @psalm-suppress InternalMethod */
            $revIndexName = $this->config->getRevisionFieldName().'_'.md5($revisionJoinTable->getName()).'_idx'; // @phpstan-ignore-line
        }
        $revisionJoinTable->addIndex([$this->config->getRevisionFieldName()], $revIndexName);
    }
}
