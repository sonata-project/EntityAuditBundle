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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ToolEvents;
use Psr\Clock\ClockInterface;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\AuditReader;
use SimpleThings\EntityAudit\EventListener\CacheListener;
use SimpleThings\EntityAudit\EventListener\CreateSchemaListener;
use SimpleThings\EntityAudit\EventListener\LogRevisionsListener;
use SimpleThings\EntityAudit\User\TokenStorageUsernameCallable;
use Symfony\Component\DependencyInjection\Definition;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()

        ->set('simplethings.entityaudit.connection', null)

        ->set('simplethings.entityaudit.entity_manager', null)

        ->set('simplethings.entityaudit.audited_entities', [])

        ->set('simplethings.entityaudit.global_ignore_columns', [])

        ->set('simplethings.entityaudit.table_prefix', null)

        ->set('simplethings.entityaudit.table_suffix', null)

        ->set('simplethings.entityaudit.revision_field_name', null)

        ->set('simplethings.entityaudit.revision_type_field_name', null)

        ->set('simplethings.entityaudit.revision_table_name', null)

        ->set('simplethings.entityaudit.revision_id_field_type', null)

        ->set('simplethings.entityaudit.disable_foreign_keys', null);

    $containerConfigurator->services()
        ->set('simplethings_entityaudit.manager', AuditManager::class)
            ->public()
            ->args([
                service('simplethings_entityaudit.config'),
                service(ClockInterface::class)->nullOnInvalid(),
            ])
            ->alias(AuditManager::class, 'simplethings_entityaudit.manager')
                ->public()

        ->set('simplethings_entityaudit.reader', AuditReader::class)
            ->public()
            ->factory([service('simplethings_entityaudit.manager'), 'createAuditReader'])
            ->args([
                (new InlineServiceConfigurator(new Definition(EntityManager::class)))
                    ->factory([service('doctrine'), 'getManager'])
                    ->args([param('simplethings.entityaudit.entity_manager')]),
            ])
            ->alias(AuditReader::class, 'simplethings_entityaudit.reader')

        ->set('simplethings_entityaudit.log_revisions_listener', LogRevisionsListener::class)
            ->tag('doctrine.event_listener', [
                'event' => Events::onFlush,
                'connection' => (string) param('simplethings.entityaudit.connection'),
            ])
            ->tag('doctrine.event_listener', [
                'event' => Events::postPersist,
                'connection' => (string) param('simplethings.entityaudit.connection'),
            ])
            ->tag('doctrine.event_listener', [
                'event' => Events::postUpdate,
                'connection' => (string) param('simplethings.entityaudit.connection'),
            ])
            ->tag('doctrine.event_listener', [
                'event' => Events::postFlush,
                'connection' => (string) param('simplethings.entityaudit.connection'),
            ])
            ->tag('doctrine.event_listener', [
                'event' => Events::onClear,
                'connection' => (string) param('simplethings.entityaudit.connection'),
            ])
            ->args([
                service('simplethings_entityaudit.manager'),
                service(ClockInterface::class)->nullOnInvalid(),
            ])

        ->set('simplethings_entityaudit.create_schema_listener', CreateSchemaListener::class)
            ->tag('doctrine.event_listener', [
                'event' => ToolEvents::postGenerateSchemaTable,
                'connection' => (string) param('simplethings.entityaudit.connection'),
            ])
            ->tag('doctrine.event_listener', [
                'event' => ToolEvents::postGenerateSchema,
                'connection' => (string) param('simplethings.entityaudit.connection'),
            ])
            ->args([service('simplethings_entityaudit.manager')])

        ->set('simplethings_entityaudit.cache_listener', CacheListener::class)
            ->tag('doctrine.event_listener', [
                'event' => Events::onClear,
                'connection' => (string) param('simplethings.entityaudit.connection'),
            ])
            ->args([service('simplethings_entityaudit.reader')])

        ->set('simplethings_entityaudit.username_callable.token_storage', TokenStorageUsernameCallable::class)
            ->args([service('security.token_storage')])

        ->set('simplethings_entityaudit.config', AuditConfiguration::class)
            ->public()
            ->call('setAuditedEntityClasses', [param('simplethings.entityaudit.audited_entities')])
            ->call('setDisabledForeignKeys', [param('simplethings.entityaudit.disable_foreign_keys')])
            ->call('setGlobalIgnoreColumns', [param('simplethings.entityaudit.global_ignore_columns')])
            ->call('setTablePrefix', [param('simplethings.entityaudit.table_prefix')])
            ->call('setTableSuffix', [param('simplethings.entityaudit.table_suffix')])
            ->call('setRevisionTableName', [param('simplethings.entityaudit.revision_table_name')])
            ->call('setRevisionIdFieldType', [param('simplethings.entityaudit.revision_id_field_type')])
            ->call('setRevisionFieldName', [param('simplethings.entityaudit.revision_field_name')])
            ->call('setRevisionTypeFieldName', [param('simplethings.entityaudit.revision_type_field_name')])
            ->call('setUsernameCallable', [service('simplethings_entityaudit.username_callable')])
            ->alias(AuditConfiguration::class, 'simplethings_entityaudit.config');
};
