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

namespace SimpleThings\EntityAudit\Exception;

class DeletedException extends AuditException
{
    public function __construct($className, $id, $revision)
    {
        parent::__construct($className, $id, $revision);
        $this->message = sprintf(
            'Class "%s" entity id "%s" has been removed at revision %s',
            $className,
            implode(', ', $id),
            $revision
        );
    }
}
