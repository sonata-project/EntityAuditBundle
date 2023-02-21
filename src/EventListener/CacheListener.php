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

final class CacheListener implements EventSubscriber
{
    private AuditReader $auditReader;

    public function __construct(AuditReader $auditReader)
    {
        $this->auditReader = $auditReader;
    }

    public function getSubscribedEvents(): array
    {
        return [Events::onClear];
    }

    public function onClear(): void
    {
        $this->auditReader->clearEntityCache();
    }
}
