<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SimpleThings\EntityAudit\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use SimpleThings\EntityAudit\DependencyInjection\SimpleThingsEntityAuditExtension;

final class SimpleThingsEntityAuditExtensionTest extends AbstractExtensionTestCase
{
    public function testItRegistersDefaultServices(): void
    {
        $this->load([]);

        $this->assertContainerBuilderHasService('simplethings_entityaudit.manager', 'SimpleThings\EntityAudit\AuditManager');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('simplethings_entityaudit.manager', 0, 'simplethings_entityaudit.config');

        $this->assertContainerBuilderHasService('simplethings_entityaudit.reader', 'SimpleThings\EntityAudit\AuditReader');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('simplethings_entityaudit.reader', 0);

        $this->assertContainerBuilderHasService('simplethings_entityaudit.log_revisions_listener', 'SimpleThings\EntityAudit\EventListener\LogRevisionsListener');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('simplethings_entityaudit.log_revisions_listener', 0, 'simplethings_entityaudit.manager');
        $this->assertContainerBuilderHasServiceDefinitionWithTag('simplethings_entityaudit.log_revisions_listener', 'doctrine.event_subscriber', ['connection' => 'default']);

        $this->assertContainerBuilderHasService('simplethings_entityaudit.create_schema_listener', 'SimpleThings\EntityAudit\EventListener\CreateSchemaListener');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('simplethings_entityaudit.create_schema_listener', 0, 'simplethings_entityaudit.manager');
        $this->assertContainerBuilderHasServiceDefinitionWithTag('simplethings_entityaudit.create_schema_listener', 'doctrine.event_subscriber', ['connection' => 'default']);

        $this->assertContainerBuilderHasService('simplethings_entityaudit.username_callable.token_storage', 'SimpleThings\EntityAudit\User\TokenStorageUsernameCallable');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('simplethings_entityaudit.username_callable.token_storage', 0, 'service_container');

        $this->assertContainerBuilderHasService('simplethings_entityaudit.config', 'SimpleThings\EntityAudit\AuditConfiguration');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setAuditedEntityClasses', ['%simplethings.entityaudit.audited_entities%']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setGlobalIgnoreColumns', ['%simplethings.entityaudit.global_ignore_columns%']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setTablePrefix', ['%simplethings.entityaudit.table_prefix%']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setTableSuffix', ['%simplethings.entityaudit.table_suffix%']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setRevisionTableName', ['%simplethings.entityaudit.revision_table_name%']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setRevisionIdFieldType', ['%simplethings.entityaudit.revision_id_field_type%']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setRevisionFieldName', ['%simplethings.entityaudit.revision_field_name%']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setRevisionTypeFieldName', ['%simplethings.entityaudit.revision_type_field_name%']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('simplethings_entityaudit.config', 'setUsernameCallable', ['simplethings_entityaudit.username_callable']);
    }

    public function testItAliasesDefaultServices(): void
    {
        $this->load([]);

        $this->assertContainerBuilderHasAlias(
            'simplethings_entityaudit.username_callable',
            'simplethings_entityaudit.username_callable.token_storage'
        );
    }

    public function testItAliasesConfiguredServices(): void
    {
        $this->load([
            'service' => [
                'username_callable' => 'custom.username_callable',
            ],
        ]);

        $this->assertContainerBuilderHasAlias(
            'simplethings_entityaudit.username_callable',
            'custom.username_callable'
        );
    }

    public function testItSetsDefaultParameters(): void
    {
        $this->load([]);

        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.connection', 'default');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.entity_manager', 'default');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.audited_entities', []);
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.global_ignore_columns', []);
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.table_prefix', '');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.table_suffix', '_audit');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_table_name', 'revisions');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_field_name', 'rev');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_type_field_name', 'revtype');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_id_field_type', 'integer');
    }

    public function testItSetsConfiguredParameters(): void
    {
        $this->load([
            'connection' => 'my_custom_connection',
            'entity_manager' => 'my_custom_entity_manager',
            'audited_entities' => ['Entity1', 'Entity2'],
            'global_ignore_columns' => ['created_at', 'updated_at'],
            'table_prefix' => 'prefix',
            'table_suffix' => 'suffix',
            'revision_table_name' => 'log',
            'revision_id_field_type' => 'guid',
            'revision_field_name' => 'revision',
            'revision_type_field_name' => 'action',
        ]);

        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.connection', 'my_custom_connection');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.entity_manager', 'my_custom_entity_manager');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.audited_entities', ['Entity1', 'Entity2']);
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.global_ignore_columns', ['created_at', 'updated_at']);
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.table_prefix', 'prefix');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.table_suffix', 'suffix');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_table_name', 'log');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_id_field_type', 'guid');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_field_name', 'revision');
        $this->assertContainerBuilderHasParameter('simplethings.entityaudit.revision_type_field_name', 'action');

        $this->assertContainerBuilderHasServiceDefinitionWithTag('simplethings_entityaudit.log_revisions_listener', 'doctrine.event_subscriber', ['connection' => 'my_custom_connection']);
        $this->assertContainerBuilderHasServiceDefinitionWithTag('simplethings_entityaudit.create_schema_listener', 'doctrine.event_subscriber', ['connection' => 'my_custom_connection']);
    }

    protected function getContainerExtensions(): array
    {
        return [
            new SimpleThingsEntityAuditExtension(),
        ];
    }
}
