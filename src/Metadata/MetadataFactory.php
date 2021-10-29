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

namespace SimpleThings\EntityAudit\Metadata;

class MetadataFactory
{
    /**
     * @var string[]
     *
     * @phpstan-var class-string[]
     */
    private $auditedEntities = [];

    /**
     * @phpstan-param array<class-string, mixed> $auditedEntities
     */
    public function __construct(array $auditedEntities)
    {
        $this->auditedEntities = array_flip(array_filter($auditedEntities, static function ($record): bool {
            return \is_string($record) || \is_int($record);
        }));
    }

    /**
     * @param string $entity
     *
     * @phpstan-param class-string $entity
     */
    public function isAudited($entity)
    {
        return isset($this->auditedEntities[$entity]);
    }

    /**
     * @return array<string, string|int>
     *
     * @phpstan-return array<class-string, string|int>
     */
    public function getAllClassNames()
    {
        return array_flip($this->auditedEntities);
    }
}
