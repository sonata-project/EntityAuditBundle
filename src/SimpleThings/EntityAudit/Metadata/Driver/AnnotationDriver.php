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

namespace SimpleThings\EntityAudit\Metadata\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;
use SimpleThings\EntityAudit\Mapping\Annotation\Ignore;
use SimpleThings\EntityAudit\Metadata\ClassMetadata;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class AnnotationDriver implements DriverInterface
{
    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * @param AnnotationReader $reader
     */
    public function __construct(AnnotationReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param string $class
     * @param ClassMetadata $classMetadata
     * @return void
     */
    public function loadMetadataForClass($class, ClassMetadata $classMetadata)
    {
        $reflection = new \ReflectionClass($class);

        foreach ($reflection->getProperties() as $property) {
            if ($this->reader->getPropertyAnnotation($property, Ignore::class)) {
                $classMetadata->ignoredFields[$property->name] = true;
            }
        }
    }

    /**
     * @param string $class
     * @return bool
     */
    public function isTransient($class)
    {
        $reflection = new \ReflectionClass($class);

        return (bool)$this->reader->getClassAnnotation($reflection, Auditable::class);
    }

    /**
     * @return AnnotationDriver
     */
    public static function create()
    {
        // use composer autoloader
        AnnotationRegistry::registerLoader('class_exists');

        $reader = new AnnotationReader();

        return new self($reader);
    }
}