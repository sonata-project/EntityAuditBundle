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

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;

/**
 * @internal
 */
final class DeferredChangedManyToManyEntityRevisionToPersist
{
    /**
     * @param array<string, mixed>|ManyToManyOwningSideMapping $assoc
     * @param array<string, mixed>                             $entityData
     * @param ClassMetadata<object>                            $class
     * @param ClassMetadata<object>                            $targetClass
     */
    public function __construct(
        private object $entity,
        private string $revType,
        private array $entityData,
        private array|ManyToManyOwningSideMapping $assoc,
        private ClassMetadata $class,
        private ClassMetadata $targetClass,
    ) {
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getRevType(): string
    {
        return $this->revType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEntityData(): array
    {
        return $this->entityData;
    }

    /**
     * @return array<string, mixed>|ManyToManyOwningSideMapping
     */
    public function getAssoc(): array|ManyToManyOwningSideMapping
    {
        return $this->assoc;
    }

    /**
     * @return ClassMetadata<object>
     */
    public function getClass(): ClassMetadata
    {
        return $this->class;
    }

    /**
     * @return ClassMetadata<object>
     */
    public function getTargetClass(): ClassMetadata
    {
        return $this->targetClass;
    }
}
