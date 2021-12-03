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

namespace SimpleThings\EntityAudit\User;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TokenStorageUsernameCallable
{
    /**
     * NEXT_MAJOR: Inject the required services instead of using the container.
     *
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * TODO: Simplify this when dropping support for Symfony < 5.3.
     *
     * @return string|null
     */
    public function __invoke()
    {
        /** @var TokenInterface $token */
        $token = $this->container->get('security.token_storage')->getToken();
        if (null !== $token && null !== $token->getUser()) {
            // @phpstan-ignore-next-line
            return method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername();
        }

        return null;
    }
}
