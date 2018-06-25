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
use SimpleThings\EntityAudit\Mapping\Annotation\AdditionalIgnore;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;
use SimpleThings\EntityAudit\Mapping\Annotation\Ignore;
use SimpleThings\EntityAudit\Mapping\Annotation\OverrideIgnore;
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
     * @var array
     */
    private $globalIgnores;

    /**
     * @param AnnotationReader $reader
     */
    public function __construct(AnnotationReader $reader, array $globalIgnores)
    {
        $this->reader = $reader;
        $this->globalIgnores = $globalIgnores;
    }

    /**
     * @param  string        $class
     * @param  ClassMetadata $classMetadata
     * @return void
     */
    public function loadMetadataForClass($class, ClassMetadata $classMetadata)
    {
        $reflection = new \ReflectionClass($class);

        //allow additional ignores to be described.
        //This can be used for columns that may be inherited or not strictly defined by attribute or ORM mapping
        $additionalIgnores = $this->reader->getClassAnnotation($reflection,AdditionalIgnore::class);

        if ($additionalIgnores) {
            foreach ($additionalIgnores->value as $ignore) {
                $classMetadata->ignoredFields[$ignore] = true;
            }
        }

        //Add attributes that are ignored by the Ignore annotation
        foreach ($reflection->getProperties() as $property) {
            if ($this->reader->getPropertyAnnotation($property, Ignore::class)) {
                $classMetadata->ignoredFields[$property->name] = true;
            }
        }

        //include global ignores
        foreach ($this->globalIgnores as $ignore) {
            $classMetadata->ignoredFields[$ignore] = true;
        }

        //override the ignoring of an attribute
        //this removes it from the ignore fields list and is good for
        //overriding globally ignored fields in necessary circumstances
        $overrides = $this->reader->getClassAnnotation($reflection,OverrideIgnore::class);

        if ($overrides) {
            foreach ($overrides->value as $override) {
                unset($classMetadata->ignoredFields[$override]);
            }
        }
    }

    /**
     * @param  string $class
     * @return bool
     */
    public function isTransient($class)
    {
        $reflection = new \ReflectionClass($class);

        return (bool) $this->reader->getClassAnnotation($reflection, Auditable::class);
    }

    /**
     * @return AnnotationDriver
     */
    public static function create($globalIgnores = array())
    {
        // use composer autoloader
        AnnotationRegistry::registerLoader('class_exists');

        $reader = new AnnotationReader();

        return new self($reader, $globalIgnores);
    }
}
