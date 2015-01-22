<?php

namespace SimpleThings\EntityAudit;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use SimpleThings\EntityAudit\EventListener\CreateSchemaListener;
use SimpleThings\EntityAudit\EventListener\LogRevisionsListener;

/**
 * Audit Manager grants access to metadata and configuration
 * and has a factory method for audit queries.
 */
class AuditManager
{
    private $config;

    private $metadataFactory;

    private $revisionMeta = array();

    private $entityMeta = array();

    /**
     * @param AuditConfiguration $config
     */
    public function __construct(AuditConfiguration $config)
    {
        $this->config = $config;
        $this->metadataFactory = $config->createMetadataFactory();
    }

    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function createAuditReader(EntityManager $em)
    {
        return new AuditReader($em, $this->config, $this->metadataFactory);
    }

    public function registerEvents(EventManager $evm)
    {
        $evm->addEventSubscriber(new CreateSchemaListener($this));
        $evm->addEventSubscriber(new LogRevisionsListener($this));
    }

    public function getRevisionMeta()
    {
        return $this->revisionMeta;
    }

    public function setRevisionMeta($revisionMeta)
    {
        $this->revisionMeta = $revisionMeta;
    }

    public function addRevisionMeta($name, $data)
    {
        $this->revisionMeta[$name] = $data;
    }

    public function getEntityMeta($entity = null)
    {
        if (is_null($entity)) {
            return $this->entityMeta;
        } elseif(isset($this->entityMeta[spl_object_hash($entity)])) {
            return $this->entityMeta[spl_object_hash($entity)];
        } else {
            return array();
        }
    }

    public function removeEntityMeta($entity = null)
    {
        if (isset($this->entityMeta[spl_object_hash($entity)])) {
            unset($this->entityMeta[spl_object_hash($entity)]);
        }
    }

    public function setEntityMeta($entityMeta)
    {
        $this->entityMeta = $entityMeta;
    }

    public function addEntityMeta($entity, $name, $data)
    {
        $this->entityMeta[spl_object_hash($entity)][$name] = $data;
    }
}