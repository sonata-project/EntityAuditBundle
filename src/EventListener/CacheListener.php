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
use Doctrine\ORM\Events;
use SimpleThings\EntityAudit\AuditReader;

/**
 * NEXT_MAJOR: do not implement EventSubscriber interface anymore.
 */
final class CacheListener implements EventSubscriber
{
    public function __construct(private readonly AuditReader $auditReader)
    {
    }

    /**
     * NEXT_MAJOR: remove this method.
     */
    public function getSubscribedEvents(): array
    {
        return [Events::onClear];
    }

    public function onClear(): void
    {
        $this->auditReader->clearEntityCache();
    }
}
