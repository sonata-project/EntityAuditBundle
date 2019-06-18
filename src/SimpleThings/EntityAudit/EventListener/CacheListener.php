<?php

declare (strict_types=1);

namespace SimpleThings\EntityAudit\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use SimpleThings\EntityAudit\EntityCache;

class CacheListener implements EventSubscriber
{
    private $entityCache;

    public function __construct(EntityCache $entityCache)
    {
        $this->entityCache = $entityCache;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onClear,
        ];
    }

    public function onClear(): void
    {
        $this->entityCache->clear();
    }
}
