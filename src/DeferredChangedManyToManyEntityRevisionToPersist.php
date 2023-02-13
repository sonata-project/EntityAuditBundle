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

/**
 * @internal
 */
final class DeferredChangedManyToManyEntityRevisionToPersist
{
    private object $entity;
    private string $revType;

    /**
     * @var array<string, mixed>
     */
    private array $entityData;

    /**
     * @var array<string, mixed>
     */
    private array $assoc;

    /**
     * @var ClassMetadata<object>
     */
    private ClassMetadata $class;

    /**
     * @var ClassMetadata<object>
     */
    private ClassMetadata $targetClass;

    /**
     * @param array<string, mixed>  $assoc
     * @param array<string, mixed>  $entityData
     * @param ClassMetadata<object> $class
     * @param ClassMetadata<object> $targetClass
     */
    public function __construct(object $entity, string $revType, array $entityData, array $assoc, ClassMetadata $class, ClassMetadata $targetClass)
    {
        $this->entity = $entity;
        $this->revType = $revType;
        $this->entityData = $entityData;
        $this->assoc = $assoc;
        $this->class = $class;
        $this->targetClass = $targetClass;
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
     * @return array<string, mixed>
     */
    public function getAssoc(): array
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
