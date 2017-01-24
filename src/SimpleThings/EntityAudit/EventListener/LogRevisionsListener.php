<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @link http://www.simplethings.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace SimpleThings\EntityAudit\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use SimpleThings\EntityAudit\AuditManager;

class LogRevisionsListener implements EventSubscriber
{
    /**
     * @var \SimpleThings\EntityAudit\AuditConfiguration
     */
    private $config;

    /**
     * @var \SimpleThings\EntityAudit\Metadata\MetadataFactory
     */
    private $metadataFactory;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Doctrine\ORM\Mapping\QuoteStrategy
     */
    private $quoteStrategy;


    /**
     * @var array
     */
    private $insertRevisionSQL = array();

    /**
     * @var \Doctrine\ORM\UnitOfWork
     */
    private $uow;

    /**
     * @var int
     */
    private $revisionId;

    /**
     * @var array
     */
    private $extraUpdates = array();

    public function __construct(AuditManager $auditManager)
    {
        $this->config = $auditManager->getConfiguration();
        $this->metadataFactory = $auditManager->getMetadataFactory();
    }

    public function getSubscribedEvents()
    {
        return array(Events::onFlush, Events::postPersist, Events::postUpdate, Events::postFlush);
    }

    /**
     * @param PostFlushEventArgs $eventArgs
     *
     * @throws MappingException
     * @throws \Doctrine\DBAL\DBALException
     * @throws MappingException
     * @throws \Exception
     */
    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $quoteStrategy = $em->getConfiguration()->getQuoteStrategy();
        $uow = $em->getUnitOfWork();
        $connection = $em->getConnection();

        foreach ($this->extraUpdates as $entity) {
            $className = get_class($entity);
            $meta = $em->getClassMetadata($className);

            $persister = $uow->getEntityPersister($className);
            $updateData = $this->prepareUpdateData($persister, $entity);

            if (! isset($updateData[$meta->table['name']]) || ! $updateData[$meta->table['name']]) {
                continue;
            }

            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder
                ->update($this->config->getTableName($meta))
                ->where(sprintf(
                    '%s = %s',
                    $this->config->getRevisionFieldName(),
                    $queryBuilder->createNamedParameter(
                        $this->getRevisionId(),
                        $this->config->getRevisionIdFieldType()
                    )
                ));

            foreach ($meta->identifier AS $idField) {
                if (isset($meta->fieldMappings[$idField])) {
                    $queryBuilder->andWhere(sprintf(
                        '%s = %s',
                        $meta->fieldMappings[$idField]['columnName'],
                        $queryBuilder->createNamedParameter(
                            $meta->reflFields[$idField]->getValue($entity),
                            $meta->fieldMappings[$idField]['type']
                        )
                    ));
                } elseif (isset($meta->associationMappings[$idField])) {
                    $foreignEntity = $meta->reflFields[$idField]->getValue($entity);
                    $foreignMeta = $em->getClassMetadata(get_class($foreignEntity));
                    $foreignIdFields = $foreignMeta->identifier;
                    if (count($foreignIdFields) > 1) {
                        // This is not supported by Doctrine, so this should never happen, but just in case..
                        throw new \Exception(
                            sprintf('Identifier field "%s" refers to a foreign entity with a composite primary key',
                                $idField)
                        );
                    }

                    $columnName = $meta->associationMappings[$idField]['joinColumns'][0];
                    if (is_array($columnName)) {
                        if (isset($columnName['name'])) {
                            $columnName = $columnName['name'];
                        } else {
                            // Not much we can do to recover this - we need a column name...
                            throw new MappingException('Column name not set within meta');
                        }
                    }

                    $queryBuilder->andWhere(sprintf(
                        '%s = %s',
                        $columnName,
                        $queryBuilder->createNamedParameter(
                            $foreignMeta->reflFields[$foreignIdFields[0]]->getValue($foreignEntity),
                            $meta->associationMappings[$idField]['type']
                        )
                    ));
                }
            }

            foreach ($updateData[$meta->table['name']] as $column => $value) {
                $field = $meta->getFieldName($column);
                $fieldName = $meta->getFieldForColumn($column);

                $placeholder = $queryBuilder->createNamedParameter(
                    $value,
                    $this->getFieldType($em, $meta, $column, $fieldName)
                );

                if ($meta->hasField($fieldName)) {
                    $field = $quoteStrategy->getColumnName($field, $meta, $this->platform);
                    $fieldType = $meta->getTypeOfField($field);
                    if (null !== $fieldType) {
                        $type = Type::getType($fieldType);
                        if ($type->canRequireSQLConversion()) {
                            $placeholder = $type->convertToDatabaseValueSQL($placeholder, $this->platform);
                        }
                    }
                }

                $queryBuilder->set($field, $placeholder);
            }

            $queryBuilder->execute();
        }
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();

        $class = $this->em->getClassMetadata(get_class($entity));
        if (! $this->metadataFactory->isAudited($class->name)) {
            return;
        }

        $this->saveRevisionEntityData($class, $this->getOriginalEntityData($entity), 'INS');
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();

        $class = $this->em->getClassMetadata(get_class($entity));
        if (! $this->metadataFactory->isAudited($class->name)) {
            return;
        }

        $auditMetadata = $this->metadataFactory->getMetadataFor($class->name);

        // get changes => should be already computed here (is a listener)
        $changeset = $this->uow->getEntityChangeSet($entity);
        foreach (array_keys($auditMetadata->ignoredFields) as $property) {
            if (isset($changeset[$property])) {
                unset($changeset[$property]);
            }
        }

        // if we have no changes left => don't create revision log
        if (count($changeset) == 0) {
            return;
        }

        $entityData = array_merge($this->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
        $this->saveRevisionEntityData($class, $entityData, 'UPD');
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->em = $eventArgs->getEntityManager();
        $this->quoteStrategy = $this->em->getConfiguration()->getQuoteStrategy();
        $this->conn = $this->em->getConnection();
        $this->uow = $this->em->getUnitOfWork();
        $this->platform = $this->conn->getDatabasePlatform();
        $this->revisionId = null; // reset revision

        $processedEntities = array();

        foreach ($this->uow->getScheduledEntityDeletions() AS $entity) {
            //doctrine is fine deleting elements multiple times. We are not.
            $hash = $this->getHash($entity);

            if (in_array($hash, $processedEntities)) {
                continue;
            }

            $processedEntities[] = $hash;

            $class = $this->em->getClassMetadata(get_class($entity));
            if (! $this->metadataFactory->isAudited($class->name)) {
                continue;
            }

            $entityData = array_merge($this->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
            $this->saveRevisionEntityData($class, $entityData, 'DEL');
        }

        foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
            if (! $this->metadataFactory->isAudited(get_class($entity))) {
                continue;
            }

            $this->extraUpdates[spl_object_hash($entity)] = $entity;
        }

        foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
            $class = get_class($entity);

            if (! $this->metadataFactory->isAudited($class)) {
                continue;
            }

            $auditMetadata = $this->metadataFactory->getMetadataFor($class);

            // get changes => should be already computed here (is a listener)
            $changeset = $this->uow->getEntityChangeSet($entity);
            foreach (array_keys($auditMetadata->ignoredFields) as $property) {
                if (isset($changeset[$property])) {
                    unset($changeset[$property]);
                }
            }

            // if we have no changes left => don't create revision log
            if (count($changeset) == 0) {
                continue;
            }

            $this->extraUpdates[spl_object_hash($entity)] = $entity;
        }
    }

    /**
     * get original entity data, including versioned field, if "version" constraint is used
     *
     * @param mixed $entity
     *
     * @return array
     */
    private function getOriginalEntityData($entity)
    {
        $class = $this->em->getClassMetadata(get_class($entity));
        $data = $this->uow->getOriginalEntityData($entity);
        if ($class->isVersioned) {
            $versionField = $class->versionField;
            $data[$versionField] = $class->reflFields[$versionField]->getValue($entity);
        }

        return $data;
    }

    private function getRevisionId()
    {
        if (null !== $this->revisionId) {
            return $this->revisionId;
        }

        $tableName = $this->config->getRevisionTableName();
        $this->conn->insert(
            $tableName,
            array(
                'timestamp' => date_create('now'),
                'username' => $this->config->getCurrentUsername(),
            ),
            array(
                Type::DATETIME,
                Type::STRING,
            )
        );

        $sequenceName = $this->platform->supportsSequences()
            ? $this->platform->getIdentitySequenceName($tableName, 'id')
            : null;

        return $this->revisionId = $this->conn->lastInsertId($sequenceName);
    }

    /**
     * @param ClassMetadata $class
     *
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getInsertRevisionSQL($class)
    {
        if (! isset($this->insertRevisionSQL[$class->name])) {
            $placeholders = array('?', '?');
            $tableName = $this->config->getTableName($class);

            $sql = "INSERT INTO " . $tableName . " (" .
                $this->config->getRevisionFieldName() . ", " . $this->config->getRevisionTypeFieldName();

            $fields = array();

            foreach ($class->associationMappings as $field => $assoc) {
                if ($class->isInheritanceTypeJoined() && $class->isInheritedAssociation($field)) {
                    continue;
                }

                if (($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                        $fields[$sourceCol] = true;
                        $sql .= ', ' . $sourceCol;
                        $placeholders[] = '?';
                    }
                }
            }

            foreach ($class->fieldNames as $field) {
                $columnName = $class->fieldMappings[$field]['columnName'];
                if (array_key_exists($columnName, $fields)) {
                    continue;
                }

                if ($class->isInheritanceTypeJoined()
                    && $class->isInheritedField($field)
                    && ! $class->isIdentifier($field)
                ) {
                    continue;
                }

                $type = Type::getType($class->fieldMappings[$field]['type']);
                $placeholders[] = (! empty($class->fieldMappings[$field]['requireSQLConversion']))
                    ? $type->convertToDatabaseValueSQL('?', $this->platform)
                    : '?';
                $sql .= ', ' . $this->quoteStrategy->getColumnName($field, $class, $this->platform);
            }

            if (($class->isInheritanceTypeJoined() && $class->rootEntityName == $class->name)
                || $class->isInheritanceTypeSingleTable()
            ) {
                $sql .= ', ' . $class->discriminatorColumn['name'];
                $placeholders[] = '?';
            }

            $sql .= ") VALUES (" . implode(", ", $placeholders) . ")";
            $this->insertRevisionSQL[$class->name] = $sql;
        }

        return $this->insertRevisionSQL[$class->name];
    }

    /**
     * @param ClassMetadata $class
     * @param array         $entityData
     * @param string        $revType
     */
    private function saveRevisionEntityData($class, $entityData, $revType)
    {
        $params = array($this->getRevisionId(), $revType);
        $types = array(\PDO::PARAM_INT, \PDO::PARAM_STR);

        $fields = array();

        foreach ($class->associationMappings AS $field => $assoc) {
            if ($class->isInheritanceTypeJoined() && $class->isInheritedAssociation($field)) {
                continue;
            }
            if (! (($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide'])) {
                continue;
            }

            $data = isset($entityData[$field]) ? $entityData[$field] : null;
            $relatedId = false;

            if ($data !== null && $this->uow->isInIdentityMap($data)) {
                $relatedId = $this->uow->getEntityIdentifier($data);
            }

            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

            foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
                $fields[$sourceColumn] = true;
                if ($data === null) {
                    $params[] = null;
                    $types[] = \PDO::PARAM_STR;
                } else {
                    $params[] = $relatedId ? $relatedId[$targetClass->fieldNames[$targetColumn]] : null;
                    $types[] = $targetClass->getTypeOfColumn($targetColumn);
                }
            }
        }

        foreach ($class->fieldNames AS $field) {
            $columnName = $class->fieldMappings[$field]['columnName'];
            if (array_key_exists($columnName, $fields)) {
                continue;
            }

            if ($class->isInheritanceTypeJoined()
                && $class->isInheritedField($field)
                && ! $class->isIdentifier($field)
            ) {
                continue;
            }

            $params[] = isset($entityData[$field]) ? $entityData[$field] : null;
            $types[] = $class->fieldMappings[$field]['type'];
        }

        if ($class->isInheritanceTypeSingleTable()) {
            $params[] = $class->discriminatorValue;
            $types[] = $class->discriminatorColumn['type'];
        } elseif ($class->isInheritanceTypeJoined()
            && $class->name == $class->rootEntityName
        ) {
            $params[] = $entityData[$class->discriminatorColumn['name']];
            $types[] = $class->discriminatorColumn['type'];
        }

        if ($class->isInheritanceTypeJoined() && $class->name != $class->rootEntityName) {
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
                array(ClassUtils::getClass($entity)),
                $this->uow->getEntityIdentifier($entity)
            )
        );
    }

    /**
     * Modified version of BasicEntityPersister::prepareUpdateData()
     * git revision d9fc5388f1aa1751a0e148e76b4569bd207338e9 (v2.5.3)
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
        $result = array();

        if (($versioned = $classMetadata->isVersioned) != false) {
            $versionField = $classMetadata->versionField;
        }

        foreach ($uow->getEntityChangeSet($entity) as $field => $change) {
            if (isset($versionField) && $versionField == $field) {
                continue;
            }

            if (isset($classMetadata->embeddedClasses[$field])) {
                continue;
            }

            $newVal = $change[1];

            if (! isset($classMetadata->associationMappings[$field])) {
                $columnName = $classMetadata->columnNames[$field];
                $result[$persister->getOwningTable($field)][$columnName] = $newVal;

                continue;
            }

            $assoc = $classMetadata->associationMappings[$field];

            // Only owning side of x-1 associations can have a FK column.
            if (! $assoc['isOwningSide'] || ! ($assoc['type'] & ClassMetadata::TO_ONE)) {
                continue;
            }

            if ($newVal !== null) {
                if ($uow->isScheduledForInsert($newVal)) {
                    $newVal = null;
                }
            }

            $newValId = null;

            if ($newVal !== null) {
                if (! $uow->isInIdentityMap($newVal)) {
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

    /**
     * @param EntityManager $em
     * @param ClassMetadata $meta
     * @param string        $column
     * @param string        $fieldName
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getFieldType(EntityManager $em, ClassMetadata $meta, $column, $fieldName)
    {
        if (in_array($column, $meta->columnNames)) {
            return $meta->getTypeOfField($fieldName);
        }

        foreach ($meta->associationMappings as $mapping) {
            if (isset($mapping['joinColumns'])) {
                foreach ($mapping['joinColumns'] as $definition) {
                    if ($definition['name'] == $column) {
                        $targetTable = $em->getClassMetadata($mapping['targetEntity']);

                        return $targetTable->getTypeOfColumn($definition['referencedColumnName']);
                    }
                }
            }
        }

        throw new \Exception(
            sprintf('Could not resolve database type for column "%s" during extra updates', $column)
        );
    }
}
