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
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('auditable.xml');

        $auditedEntities = array();
        foreach ($configs AS $config) {
            if (isset($config['audited_entities'])) {
                $auditedEntities = array_merge($auditedEntities, $config['audited_entities']);
            }
        }
        $auditedEntities = array_unique($auditedEntities);
        $container->setParameter('simplethings.entityaudit.audited_entities', $auditedEntities);
        
        $params = array(
            'table_prefix' => '',
            'table_suffix' => '_audit',
            'revision_field_name' => 'rev',
            'revision_type_field_name' => 'revtype',
            'revision_table_name' => 'revisions',
            'revision_id_field_type' => 'integer'
        );
        foreach($params as $key=>$val) {
            $container->setParameter('simplethings.entityaudit.' . $key , isset($config[$key]) ? $config[$key] : $val);
        }
    }
}

