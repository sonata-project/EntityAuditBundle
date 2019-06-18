<?php

declare(strict_types=1);

namespace SimpleThings\EntityAudit;

class EntityCache
{
    private $entities = [];

    public function clear(): void
    {
        $this->entities = [];
    }

    public function hasEntity(string $className, string $key, string $revision): bool
    {
        return isset($this->entities[$className])
            && isset($this->entities[$className][$key])
            && isset($this->entities[$className][$key][$revision]);
    }

    public function addEntity(string $className, string $key, string $revision, $entity): void
    {
        $this->entities[$className][$key][$revision] = $entity;
    }

    public function getEntity(string $className, string $key, string $revision)
    {
        if($this->hasEntity($className, $key, $revision)) {
            return $this->entities[$className][$key][$revision];
        }
    }
}