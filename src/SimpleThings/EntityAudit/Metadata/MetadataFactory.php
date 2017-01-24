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

use Doctrine\ORM\EntityManagerInterface;
use SimpleThings\EntityAudit\Metadata\Driver\DriverInterface;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class MetadataFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var ClassMetadata[]
     */
    private $classMetadatas = [];
    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DriverInterface $driver
     */
    public function __construct(EntityManagerInterface $entityManager, DriverInterface $driver)
    {
        $this->entityManager = $entityManager;
        $this->driver = $driver;
    }

    /**
     * @param string $class
     * @return bool
     */
    public function isAudited($class)
    {
        $this->load();

        return array_key_exists($class, $this->classMetadatas);
    }

    /**
     * @param string $class
     * @return ClassMetadata
     */
    public function getMetadataFor($class)
    {
        $this->load();

        return $this->classMetadatas[$class];
    }

    /**
     * @return string[]
     */
    public function getAllClassNames()
    {
        $this->load();

        return array_keys($this->classMetadatas);
    }

    /**
     *
     */
    private function load()
    {
        if ($this->loaded) {
            return;
        }

        $doctrineClassMetadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($doctrineClassMetadatas as $doctrineClassMetadata) {
            $class = $doctrineClassMetadata->name;

            if (!$this->driver->isTransient($class)) {
                continue;
            }

            $classMetadata = new ClassMetadata($doctrineClassMetadata);

            $this->driver->loadMetadataForClass($class, $classMetadata);

            $this->classMetadatas[$class] = $classMetadata;
        }

        $this->loaded = true;
    }
}
