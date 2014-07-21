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

namespace SimpleThings\EntityAudit\DependencyInjection;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

class SimpleThingsEntityAuditExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('auditable.xml');

        foreach ($config['connections'] as $connection) {
            $container->setDefinition('simplethings_entityaudit.reader.'.$connection,
                new Definition('SimpleThings\EntityAudit\AuditReader', array(new Reference('doctrine.orm.'.$connection.'_entity_manager'))))
                ->setFactoryService('simplethings_entityaudit.manager')
                ->setFactoryMethod('createAuditReader')
            ;

            $container->setDefinition('simplethings_entityaudit.log_revifions_listener.'.$connection,
                new Definition('SimpleThings\EntityAudit\EventListener\LogRevisionsListener', array(new Reference('simplethings_entityaudit.manager'))))
                ->addTag('doctrine.event_subscriber', array('connection' => $connection))
            ;

            $container->setDefinition('simplethings_entityaudit.create_schema_listener.'.$connection,
                new Definition('SimpleThings\EntityAudit\EventListener\CreateSchemaListener', array(new Reference('simplethings_entityaudit.manager'))))
                ->addTag('doctrine.event_subscriber', array('connection' => $connection))
            ;
        }


        $configurables = array(
            'audited_entities',
            'table_prefix',
            'table_suffix',
            'revision_field_name',
            'revision_type_field_name',
            'revision_table_name',
            'revision_sequence_name',
            'revision_id_field_type'
        );

        foreach ($configurables as $key) {
            $container->setParameter("simplethings.entityaudit." . $key, $config[$key]);
        }

        if (true === $config['listener']['current_username']) {
            $loader->load('current_username.xml');
        }
    }
}
