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

namespace Sonata\EntityAuditBundle\Tests\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new DoctrineBundle(),
            new SecurityBundle(),
            new DoctrineFixturesBundle(),
            new SimpleThingsEntityAuditBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return \sprintf('%scache', $this->getBaseDir());
    }

    public function getLogDir(): string
    {
        return \sprintf('%slog', $this->getBaseDir());
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(\sprintf('%s/config/routes.yml', $this->getProjectDir()));
    }

    protected function configureContainer(ContainerBuilder $containerBuilder, LoaderInterface $loader): void
    {
        $containerBuilder->setParameter('app.base_dir', $this->getBaseDir());

        $loader->load(__DIR__.'/config/config.yml');

        if (!class_exists(IsGranted::class)) {
            $loader->load(__DIR__.'/config/config_symfony_v5.yml');
        }

        $loader->load(__DIR__.'/config/services.php');
    }

    private function getBaseDir(): string
    {
        return \sprintf('%s/entity-audit-bundle/var/', sys_get_temp_dir());
    }
}
