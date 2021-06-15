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

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\AuditReader;
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
        ->set('simplethings.entityaudit.revision_id_field_type', null);

    // Use "service" function for creating references to services when dropping support for Symfony 4.4
    // Use "param" function for creating references to parameters when dropping support for Symfony 5.1
    $containerConfigurator->services()
        ->set('simplethings_entityaudit.manager', AuditManager::class)
            ->public()
            ->args([new ReferenceConfigurator('simplethings_entityaudit.config')])
            ->alias(AuditManager::class, 'simplethings_entityaudit.manager')
                ->public()

        ->set('simplethings_entityaudit.reader', AuditReader::class)
            ->public()
            ->factory([new ReferenceConfigurator('simplethings_entityaudit.manager'), 'createAuditReader'])
            ->args([
                (new InlineServiceConfigurator(new Definition(EntityManager::class)))
                    ->factory([new ReferenceConfigurator('doctrine'), 'getManager'])
                    ->args(['%simplethings.entityaudit.entity_manager%']),
            ])
            ->alias(AuditReader::class, 'simplethings_entityaudit.reader')

        ->set('simplethings_entityaudit.log_revisions_listener', LogRevisionsListener::class)
            ->tag('doctrine.event_subscriber', ['connection' => '%simplethings.entityaudit.connection%'])
            ->args([new ReferenceConfigurator('simplethings_entityaudit.manager')])

        ->set('simplethings_entityaudit.create_schema_listener', CreateSchemaListener::class)
            ->tag('doctrine.event_subscriber', ['connection' => '%simplethings.entityaudit.connection%'])
            ->args([new ReferenceConfigurator('simplethings_entityaudit.manager')])

        ->set('simplethings_entityaudit.username_callable.token_storage', TokenStorageUsernameCallable::class)
            ->args([new ReferenceConfigurator('service_container')])

        ->set('simplethings_entityaudit.config', AuditConfiguration::class)
            ->public()
            ->call('setAuditedEntityClasses', ['%simplethings.entityaudit.audited_entities%'])
            ->call('setGlobalIgnoreColumns', ['%simplethings.entityaudit.global_ignore_columns%'])
            ->call('setConvertEnumToString', ['%simplethings.entityaudit.convert_enum_to_string%'])
            ->call('setDatabasePlatform', [
                (new InlineServiceConfigurator(new Definition(Connection::class)))
                    ->factory([new ReferenceConfigurator(Connection::class), 'getDatabasePlatform']),
            ])
            ->call('setTablePrefix', ['%simplethings.entityaudit.table_prefix%'])
            ->call('setTableSuffix', ['%simplethings.entityaudit.table_suffix%'])
            ->call('setRevisionTableName', ['%simplethings.entityaudit.revision_table_name%'])
            ->call('setRevisionIdFieldType', ['%simplethings.entityaudit.revision_id_field_type%'])
            ->call('setRevisionFieldName', ['%simplethings.entityaudit.revision_field_name%'])
            ->call('setRevisionTypeFieldName', ['%simplethings.entityaudit.revision_type_field_name%'])
            ->call('setUsernameCallable', [new ReferenceConfigurator('simplethings_entityaudit.username_callable')])
            ->alias(AuditConfiguration::class, 'simplethings_entityaudit.config');
};
