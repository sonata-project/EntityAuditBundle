<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @author Andrew Tch <andrew.tchircoff@gmail.com>
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


namespace SimpleThings\EntityAudit\Tests;

use SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;
use SimpleThings\EntityAudit\AuditConfiguration;

class SymfonyTest extends \PHPUnit_Framework_TestCase
{
    public function testThatKernelBootsCorrectly()
    {
        $kernel = new TestKernel('test', true);

        $kernel->boot();

        $container = $kernel->getContainer();

        $params = array(
            'audited_entities' => array('array', array('Entity1', 'Entity2'), 'method' => 'getAuditedEntityClasses'),
            'table_prefix' => array('string', 'test_prefix'),
            'table_suffix' => array('string', 'test_suffix'),
            'revision_field_name' => array('string', 'test_rev'),
            'revision_type_field_name' => array('string', 'test_type_field_name'),
            'revision_table_name' => array('string', 'test_table_name'),
            'revision_id_field_type' => array('string', 'test_id_field_type'),
            'global_ignore_columns' => array('array', array('column1', 'column2'))
        );

        foreach ($params as $name => $param) {
            list($type, $value) = $param;

            $paramValue = $container->getParameter('simplethings.entityaudit.'.$name);
            $this->assertEquals($value, $paramValue);

            $this->assertEquals($type, gettype($value));
        }

        $configuration = $container->get('simplethings_entityaudit.config');
        /* @var $configuration AuditConfiguration */

        $metadata = $configuration->createMetadataFactory();

        $this->assertEquals($metadata->getAllClassNames(), $params['audited_entities'][1]);
        $this->assertEquals($configuration->getGlobalIgnoreColumns(), $params['global_ignore_columns'][1]);
        $this->assertEquals($configuration->getTablePrefix(), $params['table_prefix'][1]);
        $this->assertEquals($configuration->getTableSuffix(), $params['table_suffix'][1]);
        $this->assertEquals($configuration->getRevisionFieldName(), $params['revision_field_name'][1]);
        $this->assertEquals($configuration->getRevisionTypeFieldName(), $params['revision_type_field_name'][1]);
        $this->assertEquals($configuration->getRevisionTableName(), $params['revision_table_name'][1]);
        $this->assertEquals($configuration->getRevisionIdFieldType(), $params['revision_id_field_type'][1]);
    }
}

class TestKernel extends Kernel
{
    public function registerBundles()
    {
        return array(
            new SimpleThingsEntityAuditBundle()
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/symfony.yml');
    }
}
