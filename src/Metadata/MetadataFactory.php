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
     * @var array<string, int|string>
     *
     * @phpstan-var array<class-string, int|string>
     */
    private array $auditedEntities = [];

    /**
     * @phpstan-param class-string[] $auditedEntities
     */
    public function __construct(array $auditedEntities)
    {
        // NEXT_MAJOR: Remove array_filter call.
        $this->auditedEntities = array_flip(array_filter($auditedEntities));
    }

    /**
     * @param string $entity
     *
     * @return bool
     *
     * @phpstan-param class-string $entity
     */
    public function isAudited($entity)
    {
        return isset($this->auditedEntities[$entity]);
    }

    /**
     * @return array<string|int, string>
     *
     * @phpstan-return array<string|int, class-string>
     */
    public function getAllClassNames()
    {
        return array_flip($this->auditedEntities);
    }
}
