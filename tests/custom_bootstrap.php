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

use Sonata\EntityAuditBundle\Tests\App\AppKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;

$kernel = new AppKernel($_SERVER['APP_ENV'], $_SERVER['APP_DEBUG']);
$application = new Application($kernel);
$application->setAutoExit(false);

$input = new ArrayInput([
    'command' => 'doctrine:database:drop',
    '--force' => true,
]);
$application->run($input, new NullOutput());

$input = new ArrayInput([
    'command' => 'doctrine:database:create',
    '--no-interaction' => true,
]);
$application->run($input, new NullOutput());

$input = new ArrayInput([
    'command' => 'doctrine:schema:create',
]);
$application->run($input, new NullOutput());

$input = new ArrayInput([
    'command' => 'doctrine:fixtures:load',
    '--no-interaction' => true,
]);
$application->run($input, new NullOutput());

(new Filesystem())->remove([$kernel->getCacheDir()]);
