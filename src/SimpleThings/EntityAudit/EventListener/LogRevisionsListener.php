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
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
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

    public function __construct(AuditManager $auditManager)
    {
        $this->config = $auditManager->getConfiguration();
        $this->metadataFactory = $auditManager->getMetadataFactory();
    }

    public function getSubscribedEvents()
    {
        return array(Events::onFlush, Events::postPersist, Events::postUpdate, Events::postFlush);
    }

    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();

        $uow = $em->getUnitOfWork();

        //well, this is awkward, it's a reflection again
        $uowR = new \ReflectionClass($uow);
        $extraUpdatesR = $uowR->getProperty('extraUpdates');
        $extraUpdatesR->setAccessible(true);

        $extraUpdates = $extraUpdatesR->getValue($uow);

        foreach ($extraUpdates as $update) {
            list($entity, $changeset) = $update;

            if (!$this->metadataFactory->isAudited(get_class($entity))) {
                continue;
            }

            $meta = $em->getClassMetadata(get_class($entity));

            // orm 2.5+ fix
            if (class_exists('\Doctrine\ORM\Persisters\Entity\BasicEntityPersister')) {
                $persister = new \Doctrine\ORM\Persisters\Entity\BasicEntityPersister($em, $meta);
            } else {
                $persister = new \Doctrine\ORM\Persisters\BasicEntityPersister($em, $meta);
            }

            $persisterR = new \ReflectionClass($persister);

            if ($persisterR->hasMethod('prepareUpdateData')) {
                //doctrine 2.4+
                $prepareUpdateDataR = $persisterR->getMethod('prepareUpdateData');
            } elseif ($persisterR->hasMethod('_prepareUpdateData')) {
                //doctrine < 2.4
                $prepareUpdateDataR = $persisterR->getMethod('_prepareUpdateData');
            } else {
                throw new \Exception('Can not resolve prepareUpdateData method of BasicPersister, probably a doctrine regression');
            }

            $prepareUpdateDataR->setAccessible(true);

            $updateData = $prepareUpdateDataR->invoke($persister, $entity);

            if (!isset($updateData[$meta->table['name']]) || !$updateData[$meta->table['name']]) {
                continue;
            }

            foreach ($updateData[$meta->table['name']] as $field => $value) {
                $sql = 'UPDATE '.$this->config->getTableName($meta).' '.
                    'SET '.$field.' = ? '.
                    'WHERE '.$this->config->getRevisionFieldName().' = ? ';

                $params = array($value, $this->getRevisionId());

                $types = array();

                if (in_array($field, $meta->columnNames)) {
                    $types[] = $meta->fieldMappings[$meta->getFieldForColumn($field)]['type'];
                } else {
                    //try to find column in association mappings
                    $type = null;

                    foreach ($meta->associationMappings as $mapping) {
                        if (isset($mapping['joinColumns'])) {
                            foreach ($mapping['joinColumns'] as $definition) {
                                if ($definition['name'] == $field) {
                                    $targetTable = $em->getClassMetadata($mapping['targetEntity']);
                                    $type = $targetTable->getTypeOfColumn($definition['referencedColumnName']);
                                }
                            }
                        }
                    }

                    if (is_null($type)) {
                        throw new \Exception(sprintf('Could not resolve database type for column "%s" during extra updates', $field));
                    }
                }

                $types[] = $this->config->getRevisionIdFieldType();

                foreach ($meta->identifier AS $idField) {
                    if (isset($meta->fieldMappings[$idField])) {
                        $columnName = $meta->fieldMappings[$idField]['columnName'];
                        $types[] = $meta->fieldMappings[$idField]['type'];
                    } else if (isset($meta->associationMappings[$idField])) {
                        $columnName = $meta->associationMappings[$idField]['joinColumns'][0];
                        $types[] = $meta->associationMappings[$idField]['type'];
                    }

                    $params[] = $meta->reflFields[$idField]->getValue($entity);

                    $sql .= 'AND '.$columnName.' = ?';
                }

                $this->em->getConnection()->executeQuery($sql, $params, $types);
            }
        }
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();

        $class = $this->em->getClassMetadata(get_class($entity));
        if (!$this->metadataFactory->isAudited($class->name)) {
            return;
        }

        $this->saveRevisionEntityData($class, $this->getOriginalEntityData($entity), 'INS');
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();

        $class = $this->em->getClassMetadata(get_class($entity));
        if (!$this->metadataFactory->isAudited($class->name)) {
            return;
        }

        // get changes => should be already computed here (is a listener)
        $changeset = $this->uow->getEntityChangeSet($entity);
        foreach ( $this->config->getGlobalIgnoreColumns() as $column ) {
            if ( isset($changeset[$column]) ) {
                unset($changeset[$column]);
            }
        }

        // if we have no changes left => don't create revision log
        if ( count($changeset) == 0 ) {
            return;
        }

        $entityData = array_merge($this->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
        $this->saveRevisionEntityData($class, $entityData, 'UPD');
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->em = $eventArgs->getEntityManager();
        $this->conn = $this->em->getConnection();
        $this->uow = $this->em->getUnitOfWork();
        $this->platform = $this->conn->getDatabasePlatform();
        $this->revisionId = null; // reset revision

        $processedEntities = array();

        foreach ($this->uow->getScheduledEntityDeletions() AS $entity) {
            //doctrine is fine deleting elements multiple times. We are not.
            $hash = implode(' ',
                array_merge(array(get_class($entity)),
                    $this->uow->getEntityIdentifier($entity)
                )
            );

            if (in_array($hash, $processedEntities)) {
                continue;
            }

            $processedEntities[] = $hash;

            $class = $this->em->getClassMetadata(get_class($entity));
            if (!$this->metadataFactory->isAudited($class->name)) {
                continue;
            }

            $entityData = array_merge($this->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
            $this->saveRevisionEntityData($class, $entityData, 'DEL');
        }
    }

    /**
     * get original entity data, including versioned field, if "version" constraint is used
     *
     * @param mixed $entity
     * @return array
     */
    private function getOriginalEntityData($entity)
    {
        $class = $this->em->getClassMetadata(get_class($entity));
        $data = $this->uow->getOriginalEntityData($entity);
        if( $class->isVersioned ){
            $versionField = $class->versionField;
            $data[$versionField] = $class->reflFields[$versionField]->getValue($entity);
        }
        return $data;
    }

    private function getRevisionId()
    {
        if ($this->revisionId === null) {
            $this->conn->insert($this->config->getRevisionTableName(), array(
                'timestamp'     => date_create('now'),
                'username'      => $this->config->getCurrentUsername(),
            ), array(
                Type::DATETIME,
                Type::STRING
            ));

            $sequenceName = $this->platform->supportsSequences()
                ? $this->platform->getIdentitySequenceName($this->config->getRevisionTableName(), 'id')
                : null;

            $this->revisionId = $this->conn->lastInsertId($sequenceName);
        }
        return $this->revisionId;
    }

    /**
     * @param ClassMetadata $class
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getInsertRevisionSQL($class)
    {
        if (!isset($this->insertRevisionSQL[$class->name])) {
            $placeholders = array('?', '?');
            $tableName    = $this->config->getTableName($class);

            $sql = "INSERT INTO " . $tableName . " (" .
                    $this->config->getRevisionFieldName() . ", " . $this->config->getRevisionTypeFieldName();

            $fields = array();

            foreach ($class->associationMappings as $field => $assoc) {
                if ($class->isInheritanceTypeJoined()
                    && $class->isInheritedAssociation($field)) {
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
                if (array_key_exists($field, $fields)) {
                    continue;
                }

                if ($class->isInheritanceTypeJoined()
                    && $class->isInheritedField($field)
                    && ! $class->isIdentifier($field)) {
                    continue;
                }

                $type = Type::getType($class->fieldMappings[$field]['type']);
                $placeholders[] = (!empty($class->fieldMappings[$field]['requireSQLConversion']))
                    ? $type->convertToDatabaseValueSQL('?', $this->platform)
                    : '?';
                $sql .= ', ' . $class->getQuotedColumnName($field, $this->platform);
            }

            if (($class->isInheritanceTypeJoined() && $class->rootEntityName == $class->name) || $class->isInheritanceTypeSingleTable()) {
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
     * @param array $entityData
     * @param string $revType
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

            $data      = isset($entityData[$field]) ? $entityData[$field] : null;
            $relatedId = false;

            if ($data !== null && $this->uow->isInIdentityMap($data)) {
                $relatedId = $this->uow->getEntityIdentifier($data);
            }

            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

            foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
                $fields[$sourceColumn] = true;
                if ($data === null) {
                    $params[] = null;
                    $types[]  = \PDO::PARAM_STR;
                } else {
                    $params[] = $relatedId ? $relatedId[$targetClass->fieldNames[$targetColumn]] : null;
                    $types[]  = $targetClass->getTypeOfColumn($targetColumn);
                }
            }
        }

        foreach ($class->fieldNames AS $field) {
            if (array_key_exists($field, $fields)) {
                continue;
            }

            if ($class->isInheritanceTypeJoined()
                && $class->isInheritedField($field)
                && !$class->isIdentifier($field)) {
                continue;
            }

            $params[] = isset($entityData[$field]) ? $entityData[$field] : null;
            $types[] = $class->fieldMappings[$field]['type'];
        }

        if ($class->isInheritanceTypeSingleTable()) {
            $params[] = $class->discriminatorValue;
            $types[] = $class->discriminatorColumn['type'];
        } elseif ($class->isInheritanceTypeJoined()
            && $class->name == $class->rootEntityName) {
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
}
