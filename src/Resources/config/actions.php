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

use SimpleThings\EntityAudit\Action\CompareAction;
use SimpleThings\EntityAudit\Action\IndexAction;
use SimpleThings\EntityAudit\Action\ViewDetailAction;
use SimpleThings\EntityAudit\Action\ViewEntityAction;
use SimpleThings\EntityAudit\Action\ViewRevisionAction;

return static function (ContainerConfigurator $containerConfigurator): void {
    // Use "service" function for creating references to services when dropping support for Symfony 4.4
    // Use "param" function for creating references to parameters when dropping support for Symfony 5.1
    $containerConfigurator->services()
        ->set(CompareAction::class, CompareAction::class)
            ->public()
            ->args([
                new ReferenceConfigurator('twig'),
                new ReferenceConfigurator('simplethings_entityaudit.reader'),
            ])

        ->set(IndexAction::class, IndexAction::class)
            ->public()
            ->args([
                new ReferenceConfigurator('twig'),
                new ReferenceConfigurator('simplethings_entityaudit.reader'),
            ])

        ->set(ViewDetailAction::class, ViewDetailAction::class)
            ->public()
            ->args([
                new ReferenceConfigurator('twig'),
                new ReferenceConfigurator('simplethings_entityaudit.reader'),
            ])

        ->set(ViewEntityAction::class, ViewEntityAction::class)
            ->public()
            ->args([
                new ReferenceConfigurator('twig'),
                new ReferenceConfigurator('simplethings_entityaudit.reader'),
            ])

        ->set(ViewRevisionAction::class, ViewRevisionAction::class)
            ->public()
            ->args([
                new ReferenceConfigurator('twig'),
                new ReferenceConfigurator('simplethings_entityaudit.reader'),
            ]);
};
