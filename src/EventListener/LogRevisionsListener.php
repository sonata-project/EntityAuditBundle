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
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Mapping\MappingException;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;

class LogRevisionsListener implements EventSubscriber
{
    private AuditConfiguration $config;

    private MetadataFactory $metadataFactory;

    private Connection $conn;

    private AbstractPlatform $platform;

    private EntityManagerInterface $em;

    private QuoteStrategy $quoteStrategy;

    /**
     * @var string[]
     *
     * @phpstan-var array<string, string>
     */
    private array $insertRevisionSQL = [];

    private UnitOfWork $uow;

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

    public function __construct(AuditManager $auditManager)
    {
        $this->config = $auditManager->getConfiguration();
        $this->metadataFactory = $auditManager->getMetadataFactory();
    }

    /**
     * @todo Remove the "@return string[]" docblock when support for "symfony/error-handler" 5.x is dropped.
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [Events::onFlush, Events::postPersist, Events::postUpdate, Events::postFlush];
    }

    /**
     * @throws MappingException
     * @throws Exception
     * @throws \Exception
     */
    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getEntityManager();
        $quoteStrategy = $em->getConfiguration()->getQuoteStrategy();
        $uow = $em->getUnitOfWork();

        foreach ($this->extraUpdates as $entity) {
            $className = \get_class($entity);
            $meta = $em->getClassMetadata($className);

            $persister = $uow->getEntityPersister($className);
            $updateData = $this->prepareUpdateData($persister, $entity);

            if (!isset($updateData[$meta->table['name']]) || [] === $updateData[$meta->table['name']]) {
                continue;
            }

            foreach ($updateData[$meta->table['name']] as $column => $value) {
                $field = $meta->getFieldName($column);
                $fieldName = $meta->getFieldForColumn($column);
                $placeholder = '?';
                if ($meta->hasField($fieldName)) {
                    $field = $quoteStrategy->getColumnName($field, $meta, $this->platform);
                    $fieldType = $meta->getTypeOfField($field);
                    if (null !== $fieldType) {
                        $type = Type::getType($fieldType);
                        if ($type->canRequireSQLConversion()) {
                            $placeholder = $type->convertToDatabaseValueSQL('?', $this->platform);
                        }
                    }
                }

                $sql = 'UPDATE '.$this->config->getTableName($meta).' '.
                    'SET '.$field.' = '.$placeholder.' '.
                    'WHERE '.$this->config->getRevisionFieldName().' = ? ';

                $params = [$value, $this->getRevisionId()];

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
                    } elseif (isset($meta->associationMappings[$idField])) {
                        $columnName = $meta->associationMappings[$idField]['joinColumns'][0];
                        if (\is_array($columnName)) {
                            if (!isset($columnName['name'])) {
                                // Not much we can do to recover this - we need a column name...
                                throw new MappingException('Column name not set within meta');
                            }

                            $columnName = $columnName['name'];
                        }
                        $types[] = $meta->associationMappings[$idField]['type'];
                    }

                    $params[] = $meta->reflFields[$idField]->getValue($entity);

                    \assert(isset($columnName));

                    $sql .= ' AND '.$columnName.' = ?';
                }

                $this->em->getConnection()->executeQuery($sql, $params, $types);
            }
        }
    }

    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();

        $class = $this->em->getClassMetadata(\get_class($entity));
        if (!$this->metadataFactory->isAudited($class->name)) {
            return;
        }

        $this->saveRevisionEntityData($class, $this->getOriginalEntityData($entity), 'INS');
    }

    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();

        $class = $this->em->getClassMetadata(\get_class($entity));
        if (!$this->metadataFactory->isAudited($class->name)) {
            return;
        }

        // get changes => should be already computed here (is a listener)
        $changeset = $this->uow->getEntityChangeSet($entity);
        foreach ($this->config->getGlobalIgnoreColumns() as $column) {
            if (isset($changeset[$column])) {
                unset($changeset[$column]);
            }
        }

        // if we have no changes left => don't create revision log
        if (0 === \count($changeset)) {
            return;
        }

        $entityData = array_merge($this->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
        $this->saveRevisionEntityData($class, $entityData, 'UPD');
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $this->em = $eventArgs->getEntityManager();
        $this->quoteStrategy = $this->em->getConfiguration()->getQuoteStrategy();
        $this->conn = $this->em->getConnection();
        $this->uow = $this->em->getUnitOfWork();
        $this->platform = $this->conn->getDatabasePlatform();
        $this->revisionId = null; // reset revision

        $processedEntities = [];

        foreach ($this->uow->getScheduledEntityDeletions() as $entity) {
            // doctrine is fine deleting elements multiple times. We are not.
            $hash = $this->getHash($entity);

            if (\in_array($hash, $processedEntities, true)) {
                continue;
            }

            $processedEntities[] = $hash;

            $class = $this->em->getClassMetadata(\get_class($entity));
            if (!$this->metadataFactory->isAudited($class->name)) {
                continue;
            }

            $entityData = array_merge($this->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
            $this->saveRevisionEntityData($class, $entityData, 'DEL');
        }

        foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
            if (!$this->metadataFactory->isAudited(\get_class($entity))) {
                continue;
            }

            $this->extraUpdates[spl_object_hash($entity)] = $entity;
        }

        foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
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
    private function getOriginalEntityData(object $entity): array
    {
        $class = $this->em->getClassMetadata(\get_class($entity));
        $data = $this->uow->getOriginalEntityData($entity);
        if ($class->isVersioned) {
            $versionField = $class->versionField;
            $data[$versionField] = $class->reflFields[$versionField]->getValue($entity);
        }

        return $data;
    }

    /**
     * @return string|int
     */
    private function getRevisionId()
    {
        if (null === $this->revisionId) {
            $this->conn->insert(
                $this->config->getRevisionTableName(),
                [
                    'timestamp' => date_create('now'),
                    'username' => $this->config->getCurrentUsername(),
                ],
                [
                    Types::DATETIME_MUTABLE,
                    Types::STRING,
                ]
            );

            $sequenceName = $this->platform->supportsSequences()
                ? $this->platform->getIdentitySequenceName($this->config->getRevisionTableName(), 'id')
                : null;

            $revisionId = $this->conn->lastInsertId($sequenceName);
            if (false === $revisionId) {
                throw new \RuntimeException('Unable to retrieve the last revision id.');
            }

            $this->revisionId = $revisionId;
        }

        return $this->revisionId;
    }

    /**
     * @throws Exception
     */
    private function getInsertRevisionSQL(ClassMetadata $class): string
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

                if (($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide'] === true) {
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

                $type = Type::getType($class->fieldMappings[$field]['type']);
                $placeholders[] = (!empty($class->fieldMappings[$field]['requireSQLConversion']))
                    ? $type->convertToDatabaseValueSQL('?', $this->platform)
                    : '?';
                $sql .= ', '.$this->quoteStrategy->getColumnName($field, $class, $this->platform);
            }

            if (($class->isInheritanceTypeJoined() && $class->rootEntityName === $class->name)
                || $class->isInheritanceTypeSingleTable()
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
     * @param array<string, object> $entityData
     */
    private function saveRevisionEntityData(ClassMetadata $class, array $entityData, string $revType): void
    {
        $params = [$this->getRevisionId(), $revType];
        $types = [\PDO::PARAM_INT, \PDO::PARAM_STR];

        $fields = [];

        foreach ($class->associationMappings as $field => $assoc) {
            if ($class->isInheritanceTypeJoined() && $class->isInheritedAssociation($field)) {
                continue;
            }
            if (!(($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide'])) {
                continue;
            }

            $data = $entityData[$field] ?? null;
            $relatedId = false;

            if (null !== $data && $this->uow->isInIdentityMap($data)) {
                $relatedId = $this->uow->getEntityIdentifier($data);
            }

            /** @var class-string $targetEntity */
            $targetEntity = $assoc['targetEntity'];
            $targetClass = $this->em->getClassMetadata($targetEntity);

            foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
                $fields[$sourceColumn] = true;
                if (null === $data) {
                    $params[] = null;
                    $types[] = \PDO::PARAM_STR;
                } else {
                    $params[] = $relatedId ? $relatedId[$targetClass->fieldNames[$targetColumn]] : null;
                    $types[] = $targetClass->getTypeOfField($targetClass->getFieldForColumn($targetColumn));
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

        if ($class->isInheritanceTypeSingleTable()) {
            $params[] = $class->discriminatorValue;
            $types[] = $class->discriminatorColumn['type'];
        } elseif ($class->isInheritanceTypeJoined()
            && $class->name === $class->rootEntityName
        ) {
            $params[] = $entityData[$class->discriminatorColumn['name']];
            $types[] = $class->discriminatorColumn['type'];
        }

        if ($class->isInheritanceTypeJoined() && $class->name !== $class->rootEntityName) {
            $entityData[$class->discriminatorColumn['name']] = $class->discriminatorValue;
            $this->saveRevisionEntityData(
                $this->em->getClassMetadata($class->rootEntityName),
                $entityData,
                $revType
            );
        }

        $this->conn->executeStatement($this->getInsertRevisionSQL($class), $params, $types);
    }

    private function getHash(object $entity): string
    {
        return implode(
            ' ',
            array_merge(
                [\get_class($entity)],
                $this->uow->getEntityIdentifier($entity)
            )
        );
    }

    /**
     * Modified version of \Doctrine\ORM\Persisters\Entity\BasicEntityPersister::prepareUpdateData()
     * git revision d9fc5388f1aa1751a0e148e76b4569bd207338e9 (v2.5.3).
     *
     * @license MIT
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
    private function prepareUpdateData(EntityPersister $persister, object $entity): array
    {
        $uow = $this->em->getUnitOfWork();
        $classMetadata = $persister->getClassMetadata();

        $versionField = null;
        $result = [];

        if (false !== ($versioned = $classMetadata->isVersioned)) {
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
            if (!$assoc['isOwningSide'] || !($assoc['type'] & ClassMetadata::TO_ONE)) {
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
            $targetClass = $this->em->getClassMetadata($targetEntity);
            $owningTable = $persister->getOwningTable($field);

            foreach ($assoc['joinColumns'] as $joinColumn) {
                $sourceColumn = $joinColumn['name'];
                $targetColumn = $joinColumn['referencedColumnName'];

                $result[$owningTable][$sourceColumn] = $newValId
                    ? $newValId[$targetClass->getFieldForColumn($targetColumn)]
                    : null;
            }
        }

        return $result;
    }
}
