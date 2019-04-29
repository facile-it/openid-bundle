<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Security\Authentication\Provider;

use Facile\OpenIdBundle\Exception\AuthenticationException;
use Facile\OpenIdBundle\Security\Authentication\Token\OpenIdToken;
use Facile\OpenIdBundle\Security\UserProvider;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OpenIdProvider implements AuthenticationProviderInterface
{
    /** @var UserProvider */
    private $userProvider;

    public function __construct(UserProvider $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    public function authenticate(TokenInterface $token)
    {
        if (! $token instanceof OpenIdToken) {
            throw new \InvalidArgumentException('Expecting OpenIdToken, got ' . \get_class($token));
        }

//        if (! $parsedToken->verify($signer)) {
//            throw new AuthenticationException();
//        }

        if ($user = $this->userProvider->findUserByToken($token)) {
            $authenticatedToken = new OpenIdToken($token->getOpenIdToken(), $user->getRoles());
            $authenticatedToken->setUser($user);

            return $authenticatedToken;
        }

        throw new AuthenticationException();
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof OpenIdToken;
    }
}
