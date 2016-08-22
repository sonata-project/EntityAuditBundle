<?php

namespace SimpleThings\EntityAudit\User;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TokenStorageUsernameCallable
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return string|null
     */
    public function __invoke()
    {
        /** @var TokenInterface $token */
        $token = $this->container->get('security.token_storage')->getToken();
        if (null !== $token && $token->isAuthenticated()) {
            return $token->getUsername();
        }
    }
}
