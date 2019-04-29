<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Security\Firewall;

use Facile\OpenIdBundle\Security\Authentication\Token\OpenIdToken;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token as JWTToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

final class OpenIdListener implements ListenerInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var AuthenticationManagerInterface */
    private $authenticationManager;

    /** @var Parser */
    private $jwtParser;

    /**
     * OpenIdListener constructor.
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        Parser $jwtParser
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->jwtParser = $jwtParser;
    }

    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->get('_route') !== 'facile_openid_check') {
            return;
        }

        $token = new OpenIdToken($this->getJwtToken($request));

        try {
            $authToken = $this->authenticationManager->authenticate($token);
            $this->tokenStorage->setToken($authToken);

            return;
        } catch (AuthenticationException $failed) {
            // ... you might log something here

            // To deny the authentication clear the token. This will redirect to the login page.
            // Make sure to only clear your token, not those of other authentication listeners.
            $token = $this->tokenStorage->getToken();
            if ($token instanceof OpenIdToken) {
                $this->tokenStorage->setToken(null);
            }

            return;
        }
    }

    private function getJwtToken(Request $request): JWTToken
    {
        $stringToken = $request->get('id_token');

        return $this->jwtParser->parse($stringToken);
    }
}
