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
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use SimpleThings\EntityAudit\AuditConfiguration;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

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

    private $conn;
    private $platform;
    private $em;
    private $insertRevisionSql = array();
    private $uow;

    public function __construct(AuditConfiguration $config, $metadataFactory)
    {
        $this->config = $config;
        $this->metadataFactory = $metadataFactory;
    }

    public function getSubscribedEvents()
    {
        return array(Events::onFlush, Events::postPersist, Events::postUpdate);
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();

        $class = $this->em->getClassMetadata(get_class($entity));
        if (!$this->metadataFactory->isAudited($class->name)) {
            return;
        }

        $date = date_create("now")->format($this->platform->getDateFormatString());
        $this->conn->insert($this->config->getRevisionTableName(), array('timestamp' => $date));
        $revisionId = $this->conn->lastInsertId();
        $this->createRevision($revisionId, $class, $this->uow->getOriginalEntityData($entity), 'INS');
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();

        $class = $this->em->getClassMetadata(get_class($entity));
        if (!$this->metadataFactory->isAudited($class->name)) {
            return;
        }

        $date = date_create("now")->format($this->platform->getDateFormatString());
        $this->conn->insert($this->config->getRevisionTableName(), array('timestamp' => $date));
        $revisionId = $this->conn->lastInsertId();
        $entityData = array_merge($this->uow->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
        $this->createRevision($revisionId, $class, $entityData, 'UPD');
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->em = $eventArgs->getEntityManager();
        $this->conn = $this->em->getConnection();
        $this->uow = $this->em->getUnitOfWork();
        $this->platform = $this->conn->getDatabasePlatform();

        $date = date_create("now")->format($this->platform->getDateFormatString());

        foreach ($this->uow->getScheduledEntityDeletions() AS $entity) {
            $class = $this->em->getClassMetadata(get_class($entity));
            if (!$this->metadataFactory->isAudited($class->name)) {
                return;
            }

            $this->conn->insert($this->config->getRevisionTableName(), array('timestamp' => $date));
            $revisionId = $this->conn->lastInsertId();
            $entityData = array_merge($this->uow->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
            $this->createRevision($revisionId, $class, $entityData, 'DEL');
        }
    }

    /**
     *
     * @param int $revisionId
     * @param ClassMetadata $class
     * @param array $entityData
     * @param string $revType
     */
    private function createRevision($revisionId, $class, $entityData, $revType)
    {
        if (!isset($this->insertRevisionSql[$class->name])) {
            $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();
            $sql = "INSERT INTO " . $tableName . " (" .
                    $this->config->getRevisionFieldName() . ", " . $this->config->getRevisionTypeFieldName();
            foreach ($class->fieldNames AS $field) {
                $sql .= ', ' . $class->getQuotedColumnName($field, $this->platform);
            }
            $assocs = 0;
            foreach ($class->associationMappings AS $assoc) {
                if ( ($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                        $sql .= ', ' . $sourceCol;
                        $assocs++;
                    }
                }
            }
            $sql .= ") VALUES (" . implode(", ", array_fill(0, count($class->fieldNames)+$assocs+2, '?')) . ")";
            $this->insertRevisionSql[$class->name] = $sql;
        }
       
        $params = array($revisionId, $revType);
        $types = array(\PDO::PARAM_INT, \PDO::PARAM_STR);
        foreach ($class->fieldNames AS $field) {
            $params[] = $entityData[$field];
            $types[] = $class->fieldMappings[$field]['type'];
        }
        foreach ($class->associationMappings AS $field => $assoc) {
            if (($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
                $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

                if ($entityData[$field] !== null) {
                    $relatedId = $this->uow->getEntityIdentifier($entityData[$field]);
                }

                $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

                foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
                    if ($entityData[$field] === null) {
                        $params[] = null;
                        $types[] = \PDO::PARAM_STR;
                    } else {
                        $params[] = $relatedId[$targetClass->fieldNames[$targetColumn]];
                        $types[] = $targetClass->getTypeOfColumn($targetColumn);
                    }
                }
            }
        }

        $this->conn->executeUpdate($this->insertRevisionSql[$class->name], $params, $types);
    }
}