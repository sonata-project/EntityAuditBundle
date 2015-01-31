<?php

namespace SimpleThings\EntityAudit;

use Doctrine\Bundle\DoctrineBundle\Registry;
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

    private $doctrine;

    /**
     * @param AuditConfiguration $config
     * @param Registry $doctrine
     */
    public function __construct(AuditConfiguration $config, Registry $doctrine = null)
    {
        $this->config          = $config;
        $this->metadataFactory = $config->createMetadataFactory();
        $this->doctrine        = $doctrine;
    }

    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function createAuditReader(EntityManager $em = null)
    {
        if ((!$emDefault = $this->getDefaultEntityManager()) || (!$emRevision = $this->getRevisionEntityManager()))
        {
            $emDefault = $emRevision = $em;
        }

        return new AuditReader($emDefault, $emRevision, $this->config, $this->metadataFactory);
    }

    public function registerEvents(EventManager $evm)
    {
        $evm->addEventSubscriber(new CreateSchemaListener($this));
        $evm->addEventSubscriber(new LogRevisionsListener($this, $this->getRevisionEntityManager()));
    }

    public function createLogRevisionListener()
    {
        return new LogRevisionsListener($this, $this->getRevisionEntityManager());
    }

    public function getRevisionEntityManager()
    {
        return $this->doctrine ? $this->doctrine->getManager($this->config->getRevisionConnection()) : null;
    }

    public function getDefaultEntityManager()
    {
        return $this->doctrine ? $this->doctrine->getManager($this->config->getDefaultConnection()) : null;
    }
}