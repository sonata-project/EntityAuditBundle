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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TokenStorageUsernameCallable
{
    private TokenStorageInterface $tokenStorage;

    /**
     * NEXT_MAJOR: remove Container type.
     *
     * @param Container|TokenStorageInterface $tokenStorageOrContainer
     */
    public function __construct(object $tokenStorageOrContainer)
    {
        if ($tokenStorageOrContainer instanceof TokenStorageInterface) {
            $this->tokenStorage = $tokenStorageOrContainer;
        } elseif ($tokenStorageOrContainer instanceof Container) {
            @trigger_error(\sprintf(
                'Passing as argument 1 an instance of "%s" to "%s" is deprecated since'
                .' sonata-project/entity-audit-bundle 1.x and will throw an "%s" in version 2.0.'
                .' You must pass an instance of "%s" instead.',
                Container::class,
                __METHOD__,
                \TypeError::class,
                TokenStorageInterface::class
            ), \E_USER_DEPRECATED);

            $tokenStorage = $tokenStorageOrContainer->get('security.token_storage');
            \assert($tokenStorage instanceof TokenStorageInterface);
            $this->tokenStorage = $tokenStorage;
        } else {
            throw new \TypeError(\sprintf(
                'Argument 1 passed to "%s()" must be an instance of "%s" or %s, instance of "%s" given.',
                __METHOD__,
                TokenStorageInterface::class,
                Container::class,
                $tokenStorageOrContainer::class
            ));
        }
    }

    /**
     * @return string|null
     */
    public function __invoke()
    {
        $token = $this->tokenStorage->getToken();

        if (null !== $token && null !== $token->getUser()) {
            return $token->getUserIdentifier();
        }

        return null;
    }
}
