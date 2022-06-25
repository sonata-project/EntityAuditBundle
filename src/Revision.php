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

/**
 * Revision is returned from {@link AuditReader::getRevisions()}.
 */
class Revision
{
    /**
     * @var int|string
     */
    private $rev;

    private \DateTime $timestamp;

    private ?string $username;

    /**
     * @param int|string $rev
     */
    public function __construct($rev, \DateTime $timestamp, ?string $username)
    {
        $this->rev = $rev;
        $this->timestamp = $timestamp;
        $this->username = $username;
    }

    /**
     * @return int|string
     */
    public function getRev()
    {
        return $this->rev;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }
}
