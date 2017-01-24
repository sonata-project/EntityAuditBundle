<?php
/**
 * (c) SimpleThings
 *
 * @package SimpleThings\EntityAudit
 * @author  Benjamin Eberlei <eberlei@simplethings.de>
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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\DependencyInjection\SimpleThingsEntityAuditExtension;
use SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SimpleThingsEntityAuditBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultBuild()
    {
        $container = new ContainerBuilder();
        $bundle = new SimpleThingsEntityAuditBundle();
        $bundle->build($container);
        $extension = new SimpleThingsEntityAuditExtension();
        $extension->load(array(), $container);

        $connection = new Connection(
            [],
            $this->createMock(Driver::class)
        );

        $em = EntityManager::create(
            $connection,
            Setup::createAnnotationMetadataConfiguration([])
        );

        $container->set('entity_manager', $em);

        $registry = new Registry($container, [], [
            'default' => 'entity_manager'
        ], 'default', 'default');

        $container->set('doctrine', $registry);

        $container->compile();

        $auditManager = $container->get('simplethings_entityaudit.manager');
        $this->assertInstanceOf(AuditManager::class, $auditManager);
    }
}
