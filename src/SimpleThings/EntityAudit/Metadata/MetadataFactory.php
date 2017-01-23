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

namespace SimpleThings\EntityAudit\Metadata;

use SimpleThings\EntityAudit\Metadata\Driver\DriverInterface;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class MetadataFactory
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var ClassMetadata[]
     */
    private $classMetadatas;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param string $class
     * @return bool
     */
    public function isAudited($class)
    {
        $this->load($class);

        return array_key_exists($class, $this->classMetadatas);
    }

    /**
     * @param string $class
     * @return ClassMetadata
     */
    public function getMetadataFor($class)
    {
        $this->load($class);

        return $this->classMetadatas[$class];
    }

    public function getAllClassNames()
    {
        //todo
    }

    /**
     * @param string $class
     */
    private function load($class)
    {
        if (array_key_exists($class, $this->classMetadatas)) {
            return;
        }

        if (!$this->driver->isTransient($class)) {
            $this->classMetadatas[$class] = null;
        }

        $classMetadata = new ClassMetadata($class);

        $this->driver->loadMetadataForClass($class, $classMetadata);

        $this->classMetadatas[$class] = $classMetadata;
    }
}