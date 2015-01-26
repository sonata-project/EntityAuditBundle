<?php

namespace SimpleThings\EntityAudit;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\EventManager;
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

    private $emRevision;

    private $doctrine;

    /**
     * @param AuditConfiguration $config
     */
    public function __construct(AuditConfiguration $config, Registry $doctrine)
    {
        $this->config          = $config;
        $this->metadataFactory = $config->createMetadataFactory();
        $this->doctrine = $doctrine;
    }

    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function createAuditReader(Registry $doctrine)
    {
        return new AuditReader($doctrine->getManager($this->config->getDefaultConnection()), $doctrine->getManager($this->config->getRevisionConnection()), $this->config, $this->metadataFactory);
    }

    public function registerEvents(EventManager $evm)
    {
        $evm->addEventSubscriber(new CreateSchemaListener($this));
        $evm->addEventSubscriber(new LogRevisionsListener($this, $this->doctrine));
    }

    public function getRevisionEntityManager()
    {
        return $this->emRevision;
    }
}