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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;

class CreateSchemaListener implements EventSubscriber
{
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
     * @todo Remove the "@return array" docblock when support for "symfony/error-handler" 5.x is dropped.
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            ToolEvents::postGenerateSchemaTable,
            ToolEvents::postGenerateSchema,
        ];
    }

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
        $revisionTable = $schema->createTable(
            $this->config->getTablePrefix().$entityTable->getName().$this->config->getTableSuffix()
        );

        foreach ($entityTable->getColumns() as $column) {
            $this->addColumnToTable($column, $revisionTable);
        }
        $revisionTable->addColumn($this->config->getRevisionFieldName(), $this->config->getRevisionIdFieldType());
        $revisionTable->addColumn($this->config->getRevisionTypeFieldName(), Types::STRING, ['length' => 4]);
        if (!\in_array($cm->inheritanceType, [ClassMetadataInfo::INHERITANCE_TYPE_NONE, ClassMetadataInfo::INHERITANCE_TYPE_JOINED, ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE], true)) {
            throw new \Exception(sprintf('Inheritance type "%s" is not yet supported', $cm->inheritanceType));
        }

        $primaryKey = $entityTable->getPrimaryKey();
        \assert(null !== $primaryKey);
        $pkColumns = $primaryKey->getColumns();
        $pkColumns[] = $this->config->getRevisionFieldName();
        $revisionTable->setPrimaryKey($pkColumns);
        $revIndexName = $this->config->getRevisionFieldName().'_'.md5($revisionTable->getName()).'_idx';
        $revisionTable->addIndex([$this->config->getRevisionFieldName()], $revIndexName);

        foreach ($cm->associationMappings as $associationMapping) {
            if ($associationMapping['isOwningSide'] && isset($associationMapping['joinTable'])) {
                if (isset($associationMapping['joinTable']['name'])) {
                    if ($schema->hasTable($associationMapping['joinTable']['name'])) {
                        $this->createRevisionJoinTableForJoinTable($schema, $associationMapping['joinTable']['name']);
                    } else {
                        $this->defferedJoinTablesToCreate[] = $associationMapping['joinTable']['name'];
                    }
                }
            }
        }

        $revisionForeignKeyName = $this->config->getRevisionFieldName().'_'.md5($revisionTable->getName()).'_fk';

        // TODO: Use always array_keys when dropping support for DBAL 2
        $keyColumns = $revisionsTable->getPrimaryKeyColumns();
        $firstColumn = current($keyColumns);
        if ($firstColumn instanceof Column) {
            /** @var string[] $foreignColumnNames */
            $foreignColumnNames = array_keys($keyColumns);
        } else {
            /**
             * @phpstan-ignore-next-line
             *
             * @var string[] $foreignColumnNames
             */
            $foreignColumnNames = $keyColumns;
        }

        $revisionTable->addForeignKeyConstraint($revisionsTable, [$this->config->getRevisionFieldName()], $foreignColumnNames, [], $revisionForeignKeyName);
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs): void
    {
        $schema = $eventArgs->getSchema();
        $this->createRevisionsTable($schema);

        foreach ($this->defferedJoinTablesToCreate as $defferedJoinTableToCreate) {
            $this->createRevisionJoinTableForJoinTable($schema, $defferedJoinTableToCreate);
        }
    }

    /**
     * Copies $column to another table. All its options are copied but notnull and autoincrement which are set to false.
     */
    private function addColumnToTable(Column $column, Table $targetTable): void
    {
        $columnName = $column->getName();
        $targetTable->addColumn($columnName, $column->getType()->getName());

        $targetColumn = $targetTable->getColumn($columnName);
        $targetColumn->setLength($column->getLength());
        $targetColumn->setPrecision($column->getPrecision());
        $targetColumn->setScale($column->getScale());
        $targetColumn->setUnsigned($column->getUnsigned());
        $targetColumn->setFixed($column->getFixed());
        $targetColumn->setDefault($column->getDefault());
        $targetColumn->setColumnDefinition($column->getColumnDefinition());
        $targetColumn->setComment($column->getComment());
        $targetColumn->setPlatformOptions($column->getPlatformOptions());
        $targetColumn->setCustomSchemaOptions($column->getCustomSchemaOptions());

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
        $revisionsTable->addColumn('username', Types::STRING)->setNotnull(false);
        $revisionsTable->setPrimaryKey(['id']);

        return $revisionsTable;
    }

    private function createRevisionJoinTableForJoinTable(Schema $schema, string $joinTableName): void
    {
        $joinTable = $schema->getTable($joinTableName);
        $revisionJoinTableName = $this->config->getTablePrefix().$joinTable->getName().$this->config->getTableSuffix();

        if ($schema->hasTable($revisionJoinTableName)) {
            return;
        }

        $revisionJoinTable = $schema->createTable(
            $this->config->getTablePrefix().$joinTable->getName().$this->config->getTableSuffix()
        );
        foreach ($joinTable->getColumns() as $column) {
            /* @var Column $column */
            $revisionJoinTable->addColumn(
                $column->getName(),
                $column->getType()->getName(),
                ['notnull' => false, 'autoincrement' => false]
            );
        }
        $revisionJoinTable->addColumn($this->config->getRevisionFieldName(), $this->config->getRevisionIdFieldType());
        $revisionJoinTable->addColumn($this->config->getRevisionTypeFieldName(), 'string', ['length' => 4]);

        $pk = $joinTable->getPrimaryKey();
        $pkColumns = null !== $pk ? $pk->getColumns() : [];
        $pkColumns[] = $this->config->getRevisionFieldName();
        $revisionJoinTable->setPrimaryKey($pkColumns);
        $revIndexName = $this->config->getRevisionFieldName().'_'.md5($revisionJoinTable->getName()).'_idx';
        $revisionJoinTable->addIndex([$this->config->getRevisionFieldName()], $revIndexName);
    }
}
