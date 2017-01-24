<?php

namespace SimpleThings\EntityAudit\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use SimpleThings\EntityAudit\DependencyInjection\SimpleThingsEntityAuditExtension;

class SimpleThingsEntityAuditExtensionTest extends AbstractExtensionTestCase
{
    public function testItRegistersDefaultServices()
    {
        $this->load(array());

        $this->assertContainerBuilderHasService('simplethings_entityaudit.manager', 'SimpleThings\EntityAudit\AuditManager');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('simplethings_entityaudit.manager', 1, 'simplethings_entityaudit.config');

        $this->assertContainerBuilderHasService('simplethings_entityaudit.username_callable.token_storage', 'SimpleThings\EntityAudit\User\TokenStorageUsernameCallable');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('simplethings_entityaudit.username_callable.token_storage', 0, 'service_container');

        $this->assertContainerBuilderHasService('simplethings_entityaudit.config', 'SimpleThings\EntityAudit\AuditConfiguration');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setTablePrefix', array('%simplethings.entityaudit.table_prefix%'));
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setTableSuffix', array('%simplethings.entityaudit.table_suffix%'));
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setRevisionTableName', array('%simplethings.entityaudit.revision_table_name%'));
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setRevisionIdFieldType', array('%simplethings.entityaudit.revision_id_field_type%'));
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setRevisionFieldName', array('%simplethings.entityaudit.revision_field_name%'));
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setRevisionTypeFieldName', array('%simplethings.entityaudit.revision_type_field_name%'));
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setUsernameCallable', array('simplethings_entityaudit.username_callable'));
    }

    public function testItAliasesDefaultServices()
    {
        $this->load(array());

        $this->assertContainerBuilderHasAlias(
            'simplethings_entityaudit.username_callable',
            'simplethings_entityaudit.username_callable.token_storage'
        );
    }

    public function testItAliasesConfiguredServices()
    {
        $this->load(array(
            'service' => array(
                'username_callable' => 'custom.username_callable'
            )
        ));

        $this->assertContainerBuilderHasAlias(
            'simplethings_entityaudit.username_callable',
            'custom.username_callable'
        );
    }

    public function testItSetsDefaultParameters()
    {
        $this->load(array());

        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.table_prefix', '');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.table_suffix', '_audit');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_table_name', 'revisions');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_field_name', 'rev');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_type_field_name', 'revtype');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_id_field_type', 'integer');
    }

    public function testItSetsConfiguredParameters()
    {
        $this->load(array(
            'table_prefix' => 'prefix',
            'table_suffix' => 'suffix',
            'revision_table_name' => 'log',
            'revision_id_field_type' => 'guid',
            'revision_field_name' => 'revision',
            'revision_type_field_name' => 'action',
        ));

        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.table_prefix', 'prefix');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.table_suffix', 'suffix');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_table_name', 'log');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_id_field_type', 'guid');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_field_name', 'revision');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_type_field_name', 'action');
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerExtensions()
    {
        return array(
            new SimpleThingsEntityAuditExtension(),
        );
    }

    /**
     * Assert that the ContainerBuilder for this test has a service definition with the given id, which has a factory
     * definition with the given arguments.
     *
     * @param string $serviceId
     * @param string $factoryId
     * @param string $factoryMethod
     */
    protected function assertContainerBuilderHasServiceDefinitionFactory($serviceId, $factoryId, $factoryMethod)
    {
        $definition = $this->container->findDefinition($serviceId);
        $factory = $definition->getFactory();

        $this->assertEquals($factoryId, (string)$factory[0]);
        $this->assertEquals($factoryMethod, $factory[1]);
    }
}
