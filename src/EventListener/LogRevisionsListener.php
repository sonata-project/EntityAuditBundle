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
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Mapping\MappingException;
use Psr\Clock\ClockInterface;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\DeferredChangedManyToManyEntityRevisionToPersist;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;

class LogRevisionsListener implements EventSubscriber
{
    private AuditConfiguration $config;

    private MetadataFactory $metadataFactory;

    private ?ClockInterface $clock;

    /**
     * @var string[]
     *
     * @phpstan-var array<string, string>
     */
    private array $insertRevisionSQL = [];

    /**
     * @var string[]
     *
     * @phpstan-var array<string, string>
     */
    private array $insertJoinTableRevisionSQL = [];

    /**
     * @var int|string|null
     */
    private $revisionId;

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

    public function __construct(AuditManager $auditManager, ?ClockInterface $clock = null)
    {
        $this->config = $auditManager->getConfiguration();
        $this->metadataFactory = $auditManager->getMetadataFactory();
        $this->clock = $clock;
    }

    /**
     * @todo Remove the "@return string[]" docblock when support for "symfony/error-handler" 5.x is dropped.
     *
     * @return string[]
     */
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
        $em = $eventArgs->getEntityManager();
        $conn = $em->getConnection();
        $platform = $conn->getDatabasePlatform();
        $quoteStrategy = $em->getConfiguration()->getQuoteStrategy();
        $uow = $em->getUnitOfWork();

        foreach ($this->extraUpdates as $entity) {
            $className = \get_class($entity);
            $meta = $em->getClassMetadata($className);

            $persister = $uow->getEntityPersister($className);
            $updateData = $this->prepareUpdateData($em, $persister, $entity);

            if (!isset($updateData[$meta->table['name']]) || [] === $updateData[$meta->table['name']]) {
                continue;
            }

            foreach ($updateData[$meta->table['name']] as $column => $value) {
                $field = $meta->getFieldName($column);
                $fieldName = $meta->getFieldForColumn($column);
                $placeholder = '?';
                if ($meta->hasField($fieldName)) {
                    $field = $quoteStrategy->getColumnName($field, $meta, $platform);
                    $fieldType = $meta->getTypeOfField($field);
                    if (null !== $fieldType) {
                        $type = Type::getType($fieldType);
                        if ($type->canRequireSQLConversion()) {
                            $placeholder = $type->convertToDatabaseValueSQL('?', $platform);
                        }
                    }
                }

                $sql = 'UPDATE '.$this->config->getTableName($meta).' '.
                    'SET '.$field.' = '.$placeholder.' '.
                    'WHERE '.$this->config->getRevisionFieldName().' = ? ';

                $params = [$value, $this->getRevisionId($conn)];

                $types = [];

                if (\array_key_exists($column, $meta->fieldNames)) {
                    $types[] = $meta->getTypeOfField($fieldName);
                } else {
                    // try to find column in association mappings
                    $type = null;

                    foreach ($meta->associationMappings as $mapping) {
                        if (isset($mapping['joinColumns'])) {
                            foreach ($mapping['joinColumns'] as $definition) {
                                if ($definition['name'] === $column) {
                                    /** @var class-string $targetEntity */
                                    $targetEntity = $mapping['targetEntity'];
                                    $targetTable = $em->getClassMetadata($targetEntity);
                                    $type = $targetTable->getTypeOfField($targetTable->getFieldForColumn($definition['referencedColumnName']));
                                }
                            }
                        }
                    }

                    if (null === $type) {
                        throw new \Exception(sprintf('Could not resolve database type for column "%s" during extra updates', $column));
                    }

                    $types[] = $type;
                }

                $types[] = $this->config->getRevisionIdFieldType();

                foreach ($meta->identifier as $idField) {
                    if (isset($meta->fieldMappings[$idField])) {
                        $columnName = $meta->fieldMappings[$idField]['columnName'];
                        $types[] = $meta->fieldMappings[$idField]['type'];
                    } elseif (isset($meta->associationMappings[$idField]['joinColumns'])) {
                        $columnName = $meta->associationMappings[$idField]['joinColumns'][0]['name'];
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
        }

        foreach ($this->deferredChangedManyToManyEntityRevisionsToPersist as $deferredChangedManyToManyEntityRevisionToPersist) {
            $this->recordRevisionForManyToManyEntity(
                $deferredChangedManyToManyEntityRevisionToPersist->getEntity(),
                $em->getConnection(),
                $deferredChangedManyToManyEntityRevisionToPersist->getRevType(),
                $deferredChangedManyToManyEntityRevisionToPersist->getEntityData(),
                $deferredChangedManyToManyEntityRevisionToPersist->getAssoc(),
                $deferredChangedManyToManyEntityRevisionToPersist->getClass(),
                $deferredChangedManyToManyEntityRevisionToPersist->getTargetClass(),
            );
        }

        $this->deferredChangedManyToManyEntityRevisionsToPersist = [];
    }

    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $em = $eventArgs->getEntityManager();
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();

        $class = $em->getClassMetadata(\get_class($entity));
        if (!$this->metadataFactory->isAudited($class->name)) {
            return;
        }

        $entityData = array_merge(
            $this->getOriginalEntityData($em, $entity),
            $this->getManyToManyRelations($em, $entity)
        );
        $this->saveRevisionEntityData($em, $class, $entityData, 'INS');
    }

    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();

        $class = $em->getClassMetadata(\get_class($entity));
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
        $em = $eventArgs->getEntityManager();
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

            $class = $em->getClassMetadata(\get_class($entity));
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
            if (!$this->metadataFactory->isAudited(\get_class($entity))) {
                continue;
            }

            $this->extraUpdates[spl_object_hash($entity)] = $entity;
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$this->metadataFactory->isAudited(\get_class($entity))) {
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
        $class = $em->getClassMetadata(\get_class($entity));
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
        $class = $em->getClassMetadata(\get_class($entity));
        foreach ($class->associationMappings as $field => $assoc) {
            if (($assoc['type'] & ClassMetadata::MANY_TO_MANY) > 0 && $assoc['isOwningSide']) {
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

            $platform = $conn->getDatabasePlatform();
            $sequenceName = $platform->supportsSequences()
                ? $platform->getIdentitySequenceName($this->config->getRevisionTableName(), 'id')
                : null;

            $revisionId = $conn->lastInsertId($sequenceName);
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

                if (
                    ($assoc['type'] & ClassMetadata::TO_ONE) > 0
                    && true === $assoc['isOwningSide']
                    && isset($assoc['targetToSourceKeyColumns'])
                ) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
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
                $type = Type::getType($class->fieldMappings[$field]['type']);
                $placeholders[] = true === ($class->fieldMappings[$field]['requireSQLConversion'] ?? false)
                    ? $type->convertToDatabaseValueSQL('?', $platform)
                    : '?';
                $sql .= ', '.$em->getConfiguration()->getQuoteStrategy()->getColumnName($field, $class, $platform);
            }

            if (
                (
                    $class->isInheritanceTypeJoined() && $class->rootEntityName === $class->name
                    || $class->isInheritanceTypeSingleTable()
                )
                && null !== $class->discriminatorColumn
            ) {
                $sql .= ', '.$class->discriminatorColumn['name'];
                $placeholders[] = '?';
            }

            $sql .= ') VALUES ('.implode(', ', $placeholders).')';
            $this->insertRevisionSQL[$class->name] = $sql;
        }

        return $this->insertRevisionSQL[$class->name];
    }

    /**
     * @param ClassMetadata<object> $class
     * @param ClassMetadata<object> $targetClass
     * @param array<string, mixed>  $assoc
     */
    private function getInsertJoinTableRevisionSQL(ClassMetadata $class, ClassMetadata $targetClass, array $assoc): string
    {
        $cacheKey = $class->name.'.'.$targetClass->name;
        if (!isset($this->insertJoinTableRevisionSQL[$cacheKey])
            && isset($assoc['relationToSourceKeyColumns'], $assoc['relationToTargetKeyColumns'], $assoc['joinTable']['name'])) {
            $placeholders = ['?', '?'];

            $tableName = $this->config->getTablePrefix().$assoc['joinTable']['name'].$this->config->getTableSuffix();

            $sql = sprintf(
                'INSERT INTO %s (%s, %s',
                $tableName,
                $this->config->getRevisionFieldName(),
                $this->config->getRevisionTypeFieldName()
            );

            $fields = [];

            foreach ($assoc['relationToSourceKeyColumns'] as $sourceColumn => $targetColumn) {
                $fields[$sourceColumn] = true;
                $sql .= ', '.$sourceColumn;
                $placeholders[] = '?';
            }
            foreach ($assoc['relationToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
                $fields[$sourceColumn] = true;
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

            if ($assoc['isOwningSide']) {
                if (0 !== ($assoc['type'] & ClassMetadata::TO_ONE)
                    && isset($assoc['sourceToTargetKeyColumns'])) {
                    $data = $entityData[$field] ?? null;
                    $relatedId = [];

                    if (null !== $data && $uow->isInIdentityMap($data)) {
                        $relatedId = $uow->getEntityIdentifier($data);
                    }

                    /** @var class-string $targetEntity */
                    $targetEntity = $assoc['targetEntity'];
                    $targetClass = $em->getClassMetadata($targetEntity);

                    foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
                        $fields[$sourceColumn] = true;
                        if (null === $data) {
                            $params[] = null;
                            $types[] = \PDO::PARAM_STR;
                        } else {
                            $params[] = $relatedId[$targetClass->fieldNames[$targetColumn]] ?? null;
                            $types[] = $targetClass->getTypeOfField($targetClass->getFieldForColumn($targetColumn));
                        }
                    }
                } elseif (($assoc['type'] & ClassMetadata::MANY_TO_MANY) > 0
                    && isset($assoc['relationToSourceKeyColumns'], $assoc['relationToTargetKeyColumns'])) {
                    $targetClass = $em->getClassMetadata($assoc['targetEntity']);

                    $collection = $entityData[$assoc['fieldName']];
                    if (null !== $collection) {
                        foreach ($collection as $relatedEntity) {
                            if (null === $uow->getSingleIdentifierValue($relatedEntity)) {
                                // due to the commit order of the UoW the $relatedEntity hasn't yet been flushed to the DB so it doesn't have an ID assigned yet
                                // so we have to defer writing the revision record to the DB to the postFlush event by which point we know that the entity is gonna be flushed and have the ID assigned
                                $this->deferredChangedManyToManyEntityRevisionsToPersist[] = new DeferredChangedManyToManyEntityRevisionToPersist($relatedEntity, $revType, $entityData, $assoc, $class, $targetClass);
                            } else {
                                $this->recordRevisionForManyToManyEntity($relatedEntity, $conn, $revType, $entityData, $assoc, $class, $targetClass);
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
            $types[] = $class->fieldMappings[$field]['type'];
        }

        if (
            $class->isInheritanceTypeSingleTable()
            && null !== $class->discriminatorColumn
        ) {
            $params[] = $class->discriminatorValue;
            $types[] = $class->discriminatorColumn['type'];
        } elseif (
            $class->isInheritanceTypeJoined()
            && $class->name === $class->rootEntityName
            && null !== $class->discriminatorColumn
        ) {
            $params[] = $entityData[$class->discriminatorColumn['name']];
            $types[] = $class->discriminatorColumn['type'];
        }

        if (
            $class->isInheritanceTypeJoined() && $class->name !== $class->rootEntityName
            && null !== $class->discriminatorColumn
        ) {
            $entityData[$class->discriminatorColumn['name']] = $class->discriminatorValue;
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
     * @param array<string, mixed>  $assoc
     * @param array<string, mixed>  $entityData
     * @param ClassMetadata<object> $class
     * @param ClassMetadata<object> $targetClass
     */
    private function recordRevisionForManyToManyEntity(object $relatedEntity, Connection $conn, string $revType, array $entityData, array $assoc, ClassMetadata $class, ClassMetadata $targetClass): void
    {
        $joinTableParams = [$this->getRevisionId($conn), $revType];
        $joinTableTypes = [\PDO::PARAM_INT, \PDO::PARAM_STR];
        foreach ($assoc['relationToSourceKeyColumns'] as $targetColumn) {
            $joinTableParams[] = $entityData[$class->fieldNames[$targetColumn]];
            $joinTableTypes[] = $class->getTypeOfColumn($targetColumn);
        }
        foreach ($assoc['relationToTargetKeyColumns'] as $targetColumn) {
            $reflField = $targetClass->reflFields[$targetClass->fieldNames[$targetColumn]];
            \assert(null !== $reflField);
            $joinTableParams[] = $reflField->getValue($relatedEntity);
            $joinTableTypes[] = $targetClass->getTypeOfColumn($targetColumn);
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
                [\get_class($entity)],
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
                $columnName = $classMetadata->fieldMappings[$field]['columnName'];
                $result[$persister->getOwningTable($field)][$columnName] = $newVal;

                continue;
            }

            $assoc = $classMetadata->associationMappings[$field];

            // Only owning side of x-1 associations can have a FK column.
            if (
                0 === ($assoc['type'] & ClassMetadata::TO_ONE)
                || false === $assoc['isOwningSide']
                || !isset($assoc['joinColumns'])) {
                continue;
            }

            if (null !== $newVal) {
                if ($uow->isScheduledForInsert($newVal)) {
                    $newVal = null;
                }
            }

            $newValId = null;

            if (null !== $newVal) {
                if (!$uow->isInIdentityMap($newVal)) {
                    continue;
                }

                $newValId = $uow->getEntityIdentifier($newVal);
            }

            /** @var class-string $targetEntity */
            $targetEntity = $assoc['targetEntity'];
            $targetClass = $em->getClassMetadata($targetEntity);
            $owningTable = $persister->getOwningTable($field);

            foreach ($assoc['joinColumns'] as $joinColumn) {
                $sourceColumn = $joinColumn['name'];
                $targetColumn = $joinColumn['referencedColumnName'];

                $result[$owningTable][$sourceColumn] = null !== $newValId
                    ? $newValId[$targetClass->getFieldForColumn($targetColumn)]
                    : null;
            }
        }

        return $result;
    }
}
