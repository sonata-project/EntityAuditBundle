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
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;

class LogRevisionsListener implements EventSubscriber
{
    /**
     * @var AuditConfiguration
     */
    private $config;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    /**
     * @var Connection
     */
    private $conn;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var QuoteStrategy
     */
    private $quoteStrategy;

    /**
     * @var array
     */
    private $insertRevisionSQL = [];

    /**
     * @var UnitOfWork
     */
    private $uow;

    /**
     * @var int
     */
    private $revisionId;

    /**
     * @var array
     */
    private $extraUpdates = [];

    public function __construct(AuditManager $auditManager)
    {
        $this->config = $auditManager->getConfiguration();
        $this->metadataFactory = $auditManager->getMetadataFactory();
    }

    public function getSubscribedEvents()
    {
        return [Events::onFlush, Events::postPersist, Events::postUpdate, Events::postFlush];
    }

    /**
     * @throws MappingException
     * @throws DBALException
     * @throws MappingException
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

            if (!isset($updateData[$meta->table['name']]) || !$updateData[$meta->table['name']]) {
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

                // Ignore specific fields for table.
                if ($this->config->isEntityIgnoredProperty($meta->getName(), $meta->getFieldForColumn($column))) {
                    continue;
                }

                $sql = 'UPDATE '.$this->config->getTableName($meta).' '.
                    'SET '.$field.' = '.$placeholder.' '.
                    'WHERE '.$this->config->getRevisionFieldName().' = ? ';

                $params = [$value, $this->getRevisionId()];

                $types = [];

                if (\in_array($column, $meta->columnNames, true)) {
                    $types[] = $meta->getTypeOfField($fieldName);
                } else {
                    //try to find column in association mappings
                    $type = null;

                    foreach ($meta->associationMappings as $mapping) {
                        if (isset($mapping['joinColumns'])) {
                            foreach ($mapping['joinColumns'] as $definition) {
                                if ($definition['name'] === $column) {
                                    $targetTable = $em->getClassMetadata($mapping['targetEntity']);
                                    $type = $targetTable->getTypeOfColumn($definition['referencedColumnName']);
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
                            if (isset($columnName['name'])) {
                                $columnName = $columnName['name'];
                            } else {
                                // Not much we can do to recover this - we need a column name...
                                throw new MappingException('Column name not set within meta');
                            }
                        }
                        $types[] = $meta->associationMappings[$idField]['type'];
                    }

                    $params[] = $meta->reflFields[$idField]->getValue($entity);

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

        // Make sure that ignored columns for table are removed from the changeset.
        foreach ($this->config->getEntityIgnoredProperties($class->getName()) as $fields) {
            foreach ($fields as $field) {
                $column = $class->getColumnName($field);
                if (isset($changeset[$column])) {
                    unset($changeset[$column]);
                }
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
            //doctrine is fine deleting elements multiple times. We are not.
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
     * get original entity data, including versioned field, if "version" constraint is used.
     *
     * @param mixed $entity
     *
     * @return array
     */
    private function getOriginalEntityData($entity)
    {
        $class = $this->em->getClassMetadata(\get_class($entity));
        $data = $this->uow->getOriginalEntityData($entity);
        if ($class->isVersioned) {
            $versionField = $class->versionField;
            $data[$versionField] = $class->reflFields[$versionField]->getValue($entity);
        }

        return $data;
    }

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
                    Type::DATETIME,
                    Type::STRING,
                ]
            );

            $sequenceName = $this->platform->supportsSequences()
                ? $this->platform->getIdentitySequenceName($this->config->getRevisionTableName(), 'id')
                : null;

            $this->revisionId = $this->conn->lastInsertId($sequenceName);
        }

        return $this->revisionId;
    }

    /**
     * @param ClassMetadata $class
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return string
     */
    private function getInsertRevisionSQL($class)
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

                if (($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
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

                // Ignore specific fields for table.
                if ($this->config->isEntityIgnoredProperty($class->getName(), $field)) {
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
     * @param ClassMetadata $class
     * @param array         $entityData
     * @param string        $revType
     */
    private function saveRevisionEntityData($class, $entityData, $revType): void
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

            if ($class->isIdentifier($field) && !empty($entityData[$field]) && is_scalar($entityData[$field])) {
                $params[] = $entityData[$field];
                $types[] = $class->getTypeOfField($field);

                continue;
            }

            $data = $entityData[$field] ?? null;
            $relatedId = false;

            if (null !== $data && $this->uow->isInIdentityMap($data)) {
                $relatedId = $this->uow->getEntityIdentifier($data);
            }

            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

            foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
                $fields[$sourceColumn] = true;
                if (null === $data) {
                    $params[] = null;
                    $types[] = \PDO::PARAM_STR;
                } else {
                    $params[] = $relatedId ? $relatedId[$targetClass->fieldNames[$targetColumn]] : null;
                    $types[] = $targetClass->getTypeOfColumn($targetColumn);
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

            // Ignore specific fields for table.
            if ($this->config->isEntityIgnoredProperty($class->getName(), $field)) {
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

        $this->conn->executeUpdate($this->getInsertRevisionSQL($class), $params, $types);
    }

    /**
     * @param $entity
     *
     * @return string
     */
    private function getHash($entity)
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
     * Modified version of BasicEntityPersister::prepareUpdateData()
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
     * @param EntityPersister|BasicEntityPersister $persister
     * @param                                      $entity
     *
     * @return array
     */
    private function prepareUpdateData($persister, $entity)
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
                $columnName = $classMetadata->columnNames[$field];
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

            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);
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
