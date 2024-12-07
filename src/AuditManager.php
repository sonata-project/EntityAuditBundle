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

namespace SimpleThings\EntityAudit;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Psr\Clock\ClockInterface;
use SimpleThings\EntityAudit\EventListener\CreateSchemaListener;
use SimpleThings\EntityAudit\EventListener\LogRevisionsListener;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;

/**
 * Audit Manager grants access to metadata and configuration
 * and has a factory method for audit queries.
 */
class AuditManager
{
    private readonly MetadataFactory $metadataFactory;

    public function __construct(
        private readonly AuditConfiguration $config,
        private readonly ?ClockInterface $clock = null,
    ) {
        $this->metadataFactory = $config->createMetadataFactory();
    }

    /**
     * @return MetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * @return AuditConfiguration
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * NEXT_MAJOR: Use `\Doctrine\ORM\EntityManagerInterface` for argument 1.
     *
     * @return AuditReader
     */
    public function createAuditReader(EntityManager $em)
    {
        return new AuditReader($em, $this->config, $this->metadataFactory);
    }

    public function registerEvents(EventManager $evm): void
    {
        $evm->addEventSubscriber(new CreateSchemaListener($this));
        $evm->addEventSubscriber(new LogRevisionsListener($this, $this->clock));
    }
}
