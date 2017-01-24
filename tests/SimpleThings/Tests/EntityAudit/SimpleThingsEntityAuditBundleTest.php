<?php

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
