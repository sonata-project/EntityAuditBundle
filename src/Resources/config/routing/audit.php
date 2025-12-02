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

use SimpleThings\EntityAudit\Action\CompareAction;
use SimpleThings\EntityAudit\Action\IndexAction;
use SimpleThings\EntityAudit\Action\ViewDetailAction;
use SimpleThings\EntityAudit\Action\ViewEntityAction;
use SimpleThings\EntityAudit\Action\ViewRevisionAction;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\XmlFileLoader;

return static function (RoutingConfigurator $routes) {
    foreach (debug_backtrace() as $trace) {
        if (isset($trace['object'], $trace['args'])
            /* @phpstan-ignore class.notFound */
            && $trace['object'] instanceof XmlFileLoader
            && $trace['args'][0] === __DIR__.'/audit.php'
            && $trace['args'][3] === __DIR__.'/audit.xml'
        ) {
            @trigger_error(
                sprintf(
                    'The "%s/audit.xml" routing configuration is deprecated since sonata-project/entity-audit-bundle 1.22. Import "audit.php" instead.',
                    __DIR__,
                ),
                \E_USER_DEPRECATED
            );

            break;
        }
    }

    $routes->add('simple_things_entity_audit_home', '/{page}')
        ->controller(IndexAction::class)
        ->defaults(['page' => 1])
        ->requirements(['page' => '\d+']);

    $routes->add('simple_things_entity_audit_viewrevision', '/viewrev/{rev}')
        ->controller(ViewRevisionAction::class)
        ->requirements(['page' => '\d+']);

    $routes->add('simple_things_entity_audit_viewentity_detail', '/viewent/{className}/{id}/{rev}')
        ->controller(ViewDetailAction::class)
        ->requirements(['page' => '\d+']);

    $routes->add('simple_things_entity_audit_viewentity', '/viewent/{className}/{id}')
        ->controller(ViewEntityAction::class);

    $routes->add('simple_things_entity_audit_compare', '/compare/{className}/{id}/{oldRev}/{newRev}')
        ->controller(CompareAction::class)
        ->defaults(['oldRev' => null, 'newRev' => null]);
};
