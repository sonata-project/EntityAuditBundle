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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Utility\PersisterHelper;
use Doctrine\Persistence\Mapping\MappingException;
use Psr\Clock\ClockInterface;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\DeferredChangedManyToManyEntityRevisionToPersist;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;
use SimpleThings\EntityAudit\Utils\ORMCompatibilityTrait;

/**
 * NEXT_MAJOR: do not implement EventSubscriber interface anymore.
 */
class LogRevisionsListener implements EventSubscriber
{
    use ORMCompatibilityTrait;

    private AuditConfiguration $config;

    private MetadataFactory $metadataFactory;

    /**
     * @var string[]
     *
     * @phpstan-var array<string, literal-string>
     */
    private array $insertRevisionSQL = [];

    /**
     * @var string[]
     *
     * @phpstan-var array<string, literal-string>
     */
    private array $insertJoinTableRevisionSQL = [];

    private string|int|null $revisionId = null;

    /**
     * @var object[]
     *
     * @phpstan-var array<string, object>
     */
    private array $extraUpdates = [];

    /**
     * @var array<DeferredChangedManyToManyEntityRevisionToPersist>
     */
    private array $deferredChangedManyToManyEntityRevisionsToPersist = [];

    public function __construct(
        AuditManager $auditManager,
        private ?ClockInterface $clock = null,
    ) {
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
        return [Events::onFlush, Events::postPersist, Events::postUpdate, Events::postFlush, Events::onClear];
    }

    /**
     * @throws MappingException
     * @throws Exception
     * @throws \Exception
     */
    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $conn = $em->getConnection();
        $platform = $conn->getDatabasePlatform();
        $quoteStrategy = $em->getConfiguration()->getQuoteStrategy();
        $uow = $em->getUnitOfWork();

        foreach ($this->extraUpdates as $entity) {
            $className = $entity::class;
            $meta = $em->getClassMetadata($className);

            $persister = $uow->getEntityPersister($className);
            $updateData = $this->prepareUpdateData($em, $persister, $entity);

            if (!isset($updateData[$meta->table['name']]) || [] === $updateData[$meta->table['name']]) {
                continue;
            }

            $sql = 'UPDATE '.$this->config->getTableName($meta);
            $params = $types = [];

            foreach ($updateData[$meta->table['name']] as $column => $value) {
                /** @phpstan-var literal-string $field */
                $field = $meta->getFieldName($column);
                $fieldName = $meta->getFieldForColumn($column);
                $placeholder = '?';
                if ($meta->hasField($fieldName)) {
                    /** @phpstan-var literal-string $field */
                    $field = $quoteStrategy->getColumnName($field, $meta, $platform);
                    $fieldType = $meta->getTypeOfField($field);
                    if (null !== $fieldType) {
                        $type = Type::getType($fieldType);
                        /** @phpstan-var literal-string $placeholder */
                        $placeholder = $type->convertToDatabaseValueSQL('?', $platform);
                    }
                }

                if ($column === array_key_first($updateData[$meta->table['name']])) {
                    $sql .= ' SET';
                } else {
                    $sql .= ',';
                }

                $sql .= ' '.$field.' = '.$placeholder;

                $params[] = $value;

                if (\array_key_exists($column, $meta->fieldNames)) {
                    $types[] = $meta->getTypeOfField($fieldName);
                } else {
                    // try to find column in association mappings
                    $type = null;

                    foreach ($meta->associationMappings as $mapping) {
                        if (isset($mapping['joinColumns'])) {
                            foreach ($mapping['joinColumns'] as $definition) {
                                if (self::getMappingNameValue($definition) === $column) {
                                    $targetTable = $em->getClassMetadata(self::getMappingTargetEntityValue($mapping));
                                    $type = $targetTable->getTypeOfField($targetTable->getFieldForColumn(self::getMappingValue($definition, 'referencedColumnName')));
                                }
                            }
                        }
                    }

                    if (null === $type) {
                        throw new \Exception(\sprintf('Could not resolve database type for column "%s" during extra updates', $column));
                    }

                    $types[] = $type;
                }
            }

            $sql .= ' WHERE '.$this->config->getRevisionFieldName().' = ?';
            $params[] = $this->getRevisionId($conn);
            $types[] = $this->config->getRevisionIdFieldType();

            foreach ($meta->identifier as $idField) {
                if (isset($meta->fieldMappings[$idField])) {
                    $columnName = self::getMappingColumnNameValue($meta->fieldMappings[$idField]);
                    $types[] = self::getMappingValue($meta->fieldMappings[$idField], 'type');
                } elseif (isset($meta->associationMappings[$idField]['joinColumns'])) {
                    $columnName = self::getMappingNameValue($meta->associationMappings[$idField]['joinColumns'][0]);
                    $types[] = $meta->associationMappings[$idField]['type'];
                } else {
                    throw new \RuntimeException('column name not found  for'.$idField);
                }

                $reflField = $meta->reflFields[$idField];
                \assert(null !== $reflField);
                $params[] = $reflField->getValue($entity);

                $sql .= ' AND '.$columnName.' = ?';
            }

            $em->getConnection()->executeQuery($sql, $params, $types);
        }

        foreach ($this->deferredChangedManyToManyEntityRevisionsToPersist as $deferredChangedManyToManyEntityRevisionToPersist) {
            $this->recordRevisionForManyToManyEntity(
                $deferredChangedManyToManyEntityRevisionToPersist->getEntity(),
                $em,
                $deferredChangedManyToManyEntityRevisionToPersist->getRevType(),
                $deferredChangedManyToManyEntityRevisionToPersist->getEntityData(),
                $deferredChangedManyToManyEntityRevisionToPersist->getAssoc(),
                $deferredChangedManyToManyEntityRevisionToPersist->getClass(),
                $deferredChangedManyToManyEntityRevisionToPersist->getTargetClass(),
            );
        }

        $this->deferredChangedManyToManyEntityRevisionsToPersist = [];
    }

    public function postPersist(PostPersistEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getObject();

        $class = $em->getClassMetadata($entity::class);
        if (!$this->metadataFactory->isAudited($class->name)) {
            return;
        }

        $entityData = array_merge(
            $this->getOriginalEntityData($em, $entity),
            $this->getManyToManyRelations($em, $entity)
        );
        $this->saveRevisionEntityData($em, $class, $entityData, 'INS');
    }

    public function postUpdate(PostUpdateEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();

        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getObject();

        $class = $em->getClassMetadata($entity::class);
        if (!$this->metadataFactory->isAudited($class->name)) {
            return;
        }

        // get changes => should be already computed here (is a listener)
        $changeset = $uow->getEntityChangeSet($entity);
        foreach ($this->config->getGlobalIgnoreColumns() as $column) {
            if (isset($changeset[$column])) {
                unset($changeset[$column]);
            }
        }

        // if we have no changes left => don't create revision log
        if (0 === \count($changeset)) {
            return;
        }

        $entityData = array_merge(
            $this->getOriginalEntityData($em, $entity),
            $uow->getEntityIdentifier($entity),
            $this->getManyToManyRelations($em, $entity)
        );

        $this->saveRevisionEntityData($em, $class, $entityData, 'UPD');
    }

    public function onClear(): void
    {
        $this->extraUpdates = [];
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();
        $this->revisionId = null; // reset revision

        $processedEntities = [];

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            // doctrine is fine deleting elements multiple times. We are not.
            $hash = $this->getHash($uow, $entity);

            if (\in_array($hash, $processedEntities, true)) {
                continue;
            }

            $processedEntities[] = $hash;

            $class = $em->getClassMetadata($entity::class);
            if (!$this->metadataFactory->isAudited($class->name)) {
                continue;
            }

            $entityData = array_merge(
                $this->getOriginalEntityData($em, $entity),
                $uow->getEntityIdentifier($entity),
                $this->getManyToManyRelations($em, $entity)
            );
            $this->saveRevisionEntityData($em, $class, $entityData, 'DEL');
        }

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$this->metadataFactory->isAudited($entity::class)) {
                continue;
            }

            $this->extraUpdates[spl_object_hash($entity)] = $entity;
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$this->metadataFactory->isAudited($entity::class)) {
                continue;
            }

            $this->extraUpdates[spl_object_hash($entity)] = $entity;
        }
    }

    /**
     * Get original entity data, including versioned field, if "version" constraint is used.
     *
     * @return array<string, mixed>
     */
    private function getOriginalEntityData(EntityManagerInterface $em, object $entity): array
    {
        $class = $em->getClassMetadata($entity::class);
        $data = $em->getUnitOfWork()->getOriginalEntityData($entity);
        if ($class->isVersioned) {
            $versionField = $class->versionField;
            \assert(null !== $versionField);
            $reflField = $class->reflFields[$versionField];
            \assert(null !== $reflField);
            $data[$versionField] = $reflField->getValue($entity);
        }

        return $data;
    }

    /**
     * Get many to many relations data.
     *
     * @return array<string, mixed>
     */
    private function getManyToManyRelations(EntityManagerInterface $em, object $entity): array
    {
        $data = [];
        $class = $em->getClassMetadata($entity::class);
        foreach ($class->associationMappings as $field => $assoc) {
            if (self::isManyToManyOwningSideMapping($assoc)) {
                $reflField = $class->reflFields[$field];
                \assert(null !== $reflField);
                $data[$field] = $reflField->getValue($entity);
            }
        }

        return $data;
    }

    /**
     * @return string|int
     */
    private function getRevisionId(Connection $conn)
    {
        $now = $this->clock instanceof ClockInterface ? $this->clock->now() : new \DateTimeImmutable();

        if (null === $this->revisionId) {
            $conn->insert(
                $this->config->getRevisionTableName(),
                [
                    'timestamp' => $now,
                    'username' => $this->config->getCurrentUsername(),
                ],
                [
                    Types::DATETIME_IMMUTABLE,
                    Types::STRING,
                ]
            );

            $revisionId = $conn->lastInsertId();
            if (false === $revisionId) {
                throw new \RuntimeException('Unable to retrieve the last revision id.');
            }

            $this->revisionId = $revisionId;
        }

        return $this->revisionId;
    }

    /**
     * @param ClassMetadata<object> $class
     *
     * @throws Exception
     *
     * @return literal-string
     */
    private function getInsertRevisionSQL(EntityManagerInterface $em, ClassMetadata $class): string
    {
        if (!isset($this->insertRevisionSQL[$class->name])) {
            $placeholders = ['?', '?'];
            $tableName = $this->config->getTableName($class);

            $sql = 'INSERT INTO '.$tableName.' ('.
                $this->config->getRevisionFieldName().', '.$this->config->getRevisionTypeFieldName();

            $fields = [];

            foreach ($class->associationMappings as $field => $assoc) {
                if ($class->isInheritanceTypeJoined() && $class->isInheritedAssociation($field)) {
                    continue;
                }

                if (self::isToOneOwningSide($assoc)) {
                    foreach (self::getTargetToSourceKeyColumns($assoc) as $sourceCol) {
                        $fields[$sourceCol] = true;
                        $sql .= ', '.$sourceCol;
                        $placeholders[] = '?';
                    }
                }
            }

            foreach ($class->fieldNames as $field) {
                if (\array_key_exists($field, $fields)) {
                    continue;
                }

                if ($class->isInheritanceTypeJoined()
                    && $class->isInheritedField($field)
                    && !$class->isIdentifier($field)
                ) {
                    continue;
                }

                $platform = $em->getConnection()->getDatabasePlatform();
                $type = Type::getType(self::getMappingValue($class->fieldMappings[$field], 'type'));

                /** @phpstan-var literal-string $placeholder */
                $placeholder = $type->convertToDatabaseValueSQL('?', $platform);
                $placeholders[] = $placeholder;

                /** @phpstan-var literal-string $columnName */
                $columnName = $em->getConfiguration()->getQuoteStrategy()->getColumnName($field, $class, $platform);
                $sql .= ', '.$columnName;
            }

            if (
                (
                    $class->isInheritanceTypeJoined() && $class->rootEntityName === $class->name
                    || $class->isInheritanceTypeSingleTable()
                )
                && null !== $class->discriminatorColumn
            ) {
                $discriminatorColumnName = self::getMappingNameValue($class->discriminatorColumn);
                $sql .= ', '.$discriminatorColumnName;
                $placeholders[] = '?';
            }

            $sql .= ') VALUES ('.implode(', ', $placeholders).')';

            $this->insertRevisionSQL[$class->name] = $sql;
        }

        return $this->insertRevisionSQL[$class->name];
    }

    /**
     * @param ClassMetadata<object>                            $class
     * @param ClassMetadata<object>                            $targetClass
     * @param array<string, mixed>|ManyToManyOwningSideMapping $assoc
     *
     * @return literal-string
     */
    private function getInsertJoinTableRevisionSQL(
        ClassMetadata $class,
        ClassMetadata $targetClass,
        array|ManyToManyOwningSideMapping $assoc,
    ): string {
        $joinTableName = self::getMappingJoinTableNameValue($assoc);
        $cacheKey = $class->name.'.'.$targetClass->name.'.'.$joinTableName;

        if (
            !isset($this->insertJoinTableRevisionSQL[$cacheKey])
        ) {
            $placeholders = ['?', '?'];

            $tableName = $this->config->getTablePrefix().$joinTableName.$this->config->getTableSuffix();

            $sql = 'INSERT INTO '.$tableName
                .' ('.$this->config->getRevisionFieldName().
                ', '.$this->config->getRevisionTypeFieldName();

            foreach (self::getRelationToSourceKeyColumns($assoc) as $sourceColumn => $targetColumn) {
                $sql .= ', '.$sourceColumn;
                $placeholders[] = '?';
            }

            foreach (self::getRelationToTargetKeyColumns($assoc) as $sourceColumn => $targetColumn) {
                $sql .= ', '.$sourceColumn;
                $placeholders[] = '?';
            }

            $sql .= ') VALUES ('.implode(', ', $placeholders).')';

            $this->insertJoinTableRevisionSQL[$cacheKey] = $sql;
        }

        return $this->insertJoinTableRevisionSQL[$cacheKey];
    }

    /**
     * @param ClassMetadata<object> $class
     * @param array<string, mixed>  $entityData
     */
    private function saveRevisionEntityData(EntityManagerInterface $em, ClassMetadata $class, array $entityData, string $revType): void
    {
        $uow = $em->getUnitOfWork();
        $conn = $em->getConnection();

        $params = [$this->getRevisionId($conn), $revType];
        $types = [\PDO::PARAM_INT, \PDO::PARAM_STR];

        $fields = [];

        foreach ($class->associationMappings as $field => $assoc) {
            if ($class->isInheritanceTypeJoined() && $class->isInheritedAssociation($field)) {
                continue;
            }

            if (self::isOwningSide($assoc)) {
                if (self::isToOneOwningSide($assoc)) {
                    $data = $entityData[$field] ?? null;
                    $relatedId = [];

                    if (\is_object($data) && $uow->isInIdentityMap($data)) {
                        $relatedId = $uow->getEntityIdentifier($data);
                    }

                    $targetClass = $em->getClassMetadata(self::getMappingTargetEntityValue($assoc));

                    foreach (self::getSourceToTargetKeyColumns($assoc) as $sourceColumn => $targetColumn) {
                        $fields[$sourceColumn] = true;
                        if (null === $data) {
                            $params[] = null;
                            $types[] = \PDO::PARAM_STR;
                        } else {
                            $params[] = $relatedId[$targetClass->fieldNames[$targetColumn]] ?? null;
                            $types[] = $targetClass->getTypeOfField($targetClass->getFieldForColumn($targetColumn));
                        }
                    }
                } elseif (self::isManyToManyOwningSideMapping($assoc)) {
                    $targetClass = $em->getClassMetadata(self::getMappingTargetEntityValue($assoc));

                    $collection = $entityData[$assoc['fieldName']];
                    if (null !== $collection) {
                        foreach ($collection as $relatedEntity) {
                            if (null === $uow->getSingleIdentifierValue($relatedEntity)) {
                                // due to the commit order of the UoW the $relatedEntity hasn't yet been flushed to the DB so it doesn't have an ID assigned yet
                                // so we have to defer writing the revision record to the DB to the postFlush event by which point we know that the entity is gonna be flushed and have the ID assigned
                                $this->deferredChangedManyToManyEntityRevisionsToPersist[] = new DeferredChangedManyToManyEntityRevisionToPersist($relatedEntity, $revType, $entityData, $assoc, $class, $targetClass);
                            } else {
                                $this->recordRevisionForManyToManyEntity($relatedEntity, $em, $revType, $entityData, $assoc, $class, $targetClass);
                            }
                        }
                    }
                }
            }
        }

        foreach ($class->fieldNames as $field) {
            if (\array_key_exists($field, $fields)) {
                continue;
            }

            if ($class->isInheritanceTypeJoined()
                && $class->isInheritedField($field)
                && !$class->isIdentifier($field)
            ) {
                continue;
            }

            $params[] = $entityData[$field] ?? null;
            $types[] = self::getMappingValue($class->fieldMappings[$field], 'type');
        }

        if (
            $class->isInheritanceTypeSingleTable()
            && null !== $class->discriminatorColumn
        ) {
            $params[] = $class->discriminatorValue;
            $types[] = self::getMappingValue($class->discriminatorColumn, 'type');
        } elseif (
            $class->isInheritanceTypeJoined()
            && $class->name === $class->rootEntityName
            && null !== $class->discriminatorColumn
        ) {
            $params[] = $entityData[self::getMappingNameValue($class->discriminatorColumn)];
            $types[] = self::getMappingValue($class->discriminatorColumn, 'type');
        }

        if (
            $class->isInheritanceTypeJoined() && $class->name !== $class->rootEntityName
            && null !== $class->discriminatorColumn
        ) {
            $entityData[self::getMappingNameValue($class->discriminatorColumn)] = $class->discriminatorValue;
            $this->saveRevisionEntityData(
                $em,
                $em->getClassMetadata($class->rootEntityName),
                $entityData,
                $revType
            );
        }

        foreach ($params as $key => $parameterValue) {
            if ($parameterValue instanceof \BackedEnum) {
                $params[$key] = $parameterValue->value;
            }
        }

        $conn->executeStatement($this->getInsertRevisionSQL($em, $class), $params, $types);
    }

    /**
     * @param array<string, mixed>|ManyToManyOwningSideMapping $assoc
     * @param array<string, mixed>                             $entityData
     * @param ClassMetadata<object>                            $class
     * @param ClassMetadata<object>                            $targetClass
     */
    private function recordRevisionForManyToManyEntity(
        object $relatedEntity,
        EntityManagerInterface $em,
        string $revType,
        array $entityData,
        array|ManyToManyOwningSideMapping $assoc,
        ClassMetadata $class,
        ClassMetadata $targetClass,
    ): void {
        $conn = $em->getConnection();
        $joinTableParams = [$this->getRevisionId($conn), $revType];
        $joinTableTypes = [\PDO::PARAM_INT, \PDO::PARAM_STR];

        foreach (self::getRelationToSourceKeyColumns($assoc) as $targetColumn) {
            $joinTableParams[] = $entityData[$class->fieldNames[$targetColumn]];
            $joinTableTypes[] = PersisterHelper::getTypeOfColumn($targetColumn, $class, $em);
        }

        foreach (self::getRelationToTargetKeyColumns($assoc) as $targetColumn) {
            $reflField = $targetClass->reflFields[$targetClass->fieldNames[$targetColumn]];
            \assert(null !== $reflField);
            $joinTableParams[] = $reflField->getValue($relatedEntity);
            $joinTableTypes[] = PersisterHelper::getTypeOfColumn($targetColumn, $targetClass, $em);
        }
        $conn->executeStatement(
            $this->getInsertJoinTableRevisionSQL($class, $targetClass, $assoc),
            $joinTableParams,
            $joinTableTypes
        );
    }

    private function getHash(UnitOfWork $uow, object $entity): string
    {
        return implode(
            ' ',
            array_merge(
                [$entity::class],
                $uow->getEntityIdentifier($entity)
            )
        );
    }

    /**
     * Modified version of \Doctrine\ORM\Persisters\Entity\BasicEntityPersister::prepareUpdateData()
     * git revision d9fc5388f1aa1751a0e148e76b4569bd207338e9 (v2.5.3).
     *
     * @license MIT
     *
     * @author  Roman Borschel <roman@code-factory.org>
     * @author  Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
     * @author  Benjamin Eberlei <kontakt@beberlei.de>
     * @author  Alexander <iam.asm89@gmail.com>
     * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
     * @author  Rob Caiger <rob@clocal.co.uk>
     * @author  Simon Mönch <simonmoench@gmail.com>
     *
     * @return array<string, array<string, mixed>>
     */
    private function prepareUpdateData(EntityManagerInterface $em, EntityPersister $persister, object $entity): array
    {
        $uow = $em->getUnitOfWork();
        $classMetadata = $persister->getClassMetadata();

        $versionField = null;
        $result = [];

        if (false !== $classMetadata->isVersioned) {
            $versionField = $classMetadata->versionField;
        }

        foreach ($uow->getEntityChangeSet($entity) as $field => $change) {
            if (isset($versionField) && $versionField === $field) {
                continue;
            }

            if (isset($classMetadata->embeddedClasses[$field])) {
                continue;
            }

            $newVal = $change[1];

            if (!isset($classMetadata->associationMappings[$field])) {
                $columnName = self::getMappingColumnNameValue($classMetadata->fieldMappings[$field]);
                $result[$persister->getOwningTable($field)][$columnName] = $newVal;

                continue;
            }

            $assoc = $classMetadata->associationMappings[$field];

            // Only owning side of x-1 associations can have a FK column.
            if (!self::isToOneOwningSide($assoc)) {
                continue;
            }

            if (null !== $newVal) {
                if ($uow->isScheduledForInsert($newVal)) {
                    $newVal = null;
                }
            }

            $newValId = null;

            if (\is_object($newVal)) {
                if (!$uow->isInIdentityMap($newVal)) {
                    continue;
                }

                $newValId = $uow->getEntityIdentifier($newVal);
            }

            $targetClass = $em->getClassMetadata(self::getMappingTargetEntityValue($assoc));
            $owningTable = $persister->getOwningTable($field);

            foreach ($assoc['joinColumns'] as $joinColumn) {
                $sourceColumn = self::getMappingNameValue($joinColumn);
                $targetColumn = self::getMappingValue($joinColumn, 'referencedColumnName');

                $result[$owningTable][$sourceColumn] = null !== $newValId
                    ? $newValId[$targetClass->getFieldForColumn($targetColumn)]
                    : null;
            }
        }

        return $result;
    }
}
