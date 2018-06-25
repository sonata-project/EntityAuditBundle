<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @link http://www.simplethings.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace SimpleThings\EntityAudit;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use SimpleThings\EntityAudit\Comparator\ComparatorInterface;
use SimpleThings\EntityAudit\Metadata\Driver\AnnotationDriver;
use SimpleThings\EntityAudit\Metadata\Driver\DriverInterface;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\Container;

class AuditConfiguration
{
    /**
     * @var DriverInterface
     */
    private $metadataDriver;

    /**
     * @var string
     */
    private $tablePrefix = '';

    /**
     * @var string
     */
    private $tableSuffix = '_audit';

    /**
     * @var string
     */
    private $revisionTableName = 'revisions';

    /**
     * @var string
     */
    private $revisionFieldName = 'rev';

    /**
     * @var string
     */
    private $revisionTypeFieldName = 'revtype';

    /**
     * @var string
     */
    private $revisionIdFieldType = 'integer';

    /**
     * @var callable
     */
    private $usernameCallable;

    /**
     * @var array
     */
    private $comparators;

    public function __construct()
    {
        $this->comparators = array();
    }

    /**
     * @param DriverInterface $driver
     */
    public function setMetadataDriver(DriverInterface $driver)
    {
        $this->metadataDriver = $driver;
    }

    /**
     * @return DriverInterface
     */
    public function getMetadataDriver()
    {
        return $this->metadataDriver;
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    public function getTableName(ClassMetadataInfo $metadata)
    {
        $tableName = $this->getTablePrefix() . $metadata->getTableName() . $this->getTableSuffix();

        if (!$metadata->getSchemaName()) {
            return $tableName;
        }

        return $metadata->getSchemaName() . '.' . $tableName;
    }

    /**
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * @param string $prefix
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
    }

    /**
     * @return string
     */
    public function getTableSuffix()
    {
        return $this->tableSuffix;
    }

    /**
     * @param string $suffix
     */
    public function setTableSuffix($suffix)
    {
        $this->tableSuffix = $suffix;
    }

    /**
     * @return string
     */
    public function getRevisionFieldName()
    {
        return $this->revisionFieldName;
    }

    /**
     * @param string $revisionFieldName
     */
    public function setRevisionFieldName($revisionFieldName)
    {
        $this->revisionFieldName = $revisionFieldName;
    }

    /**
     * @return string
     */
    public function getRevisionTypeFieldName()
    {
        return $this->revisionTypeFieldName;
    }

    /**
     * @param string $revisionTypeFieldName
     */
    public function setRevisionTypeFieldName($revisionTypeFieldName)
    {
        $this->revisionTypeFieldName = $revisionTypeFieldName;
    }

    /**
     * @return string
     */
    public function getRevisionTableName()
    {
        return $this->revisionTableName;
    }

    /**
     * @param string $revisionTableName
     */
    public function setRevisionTableName($revisionTableName)
    {
        $this->revisionTableName = $revisionTableName;
    }

    /**
     * @return string|null
     */
    public function getCurrentUsername()
    {
        $callable = $this->usernameCallable;

        return $callable ? $callable() : null;
    }

    public function setUsernameCallable(callable $usernameCallable = null)
    {
        $this->usernameCallable = $usernameCallable;
    }

    /**
     * @return callable|null
     */
    public function getUsernameCallable()
    {
        return $this->usernameCallable;
    }

    public function addComparator($comparator)
    {
        if (!$comparator instanceof ComparatorInterface) {
            throw new \InvalidArgumentException('Comparator must be instance of Comparator Interface');
        }

        $this->comparators[] = $comparator;
    }

    public function setComparators($comparators, Container $container = null)
    {
        if (!$comparators || is_string($comparators)) {
            return;
        }

        if ($comparators instanceof RewindableGenerator) {
            $comparators = iterator_to_array($comparators->getIterator());
        }

        if (!is_array($comparators) && !($comparators instanceof \Traversable)) {
            throw new \InvalidArgumentException('Must be Rewindable Generator or array');
        }

        foreach ($comparators as $comparator) {
            if (is_string($comparator) && $container) {
                $comparator = $container->get($comparator);
            }
            $this->addComparator($comparator);
        }
    }

    /**
     * @return array|null
     */
    public function getComparators()
    {
        return $this->comparators;
    }

    /**
     * @param string $revisionIdFieldType
     */
    public function setRevisionIdFieldType($revisionIdFieldType)
    {
        $this->revisionIdFieldType = $revisionIdFieldType;
    }

    /**
     * @return string
     */
    public function getRevisionIdFieldType()
    {
        return $this->revisionIdFieldType;
    }

    /**
     * @return AuditConfiguration
     */
    public static function createWithAnnotationDriver()
    {
        return new self(AnnotationDriver::create());
    }
}
