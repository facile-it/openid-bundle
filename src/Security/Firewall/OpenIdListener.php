<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Security\Firewall;

use Facile\OpenIdBundle\Security\Authentication\Token\OpenIdToken;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token as JWTToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

final class OpenIdListener extends AbstractAuthenticationListener
{
    private const NONCE_SESSION_ATTRIBUTE = 'facile-openid-nonce';

    /** @var Parser */
    private $jwtParser;

    public function __construct(
        Parser $jwtParser,
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        HttpUtils $httpUtils,
        string $providerKey,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        array $options = [],
        LoggerInterface $logger = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        $this->jwtParser = $jwtParser;

        parent::__construct(
            $tokenStorage,
            $authenticationManager,
            $sessionStrategy,
            $httpUtils,
            $providerKey,
            $successHandler,
            $failureHandler,
            $options,
            $logger,
            $dispatcher
        );
    }

    protected function requiresAuthentication(Request $request): bool
    {
        if ($this->isLoginPath($request)) {
            return true;
        }

        return parent::requiresAuthentication($request);
    }

    protected function attemptAuthentication(Request $request)
    {
        if ($this->isLoginPath($request)) {
            return $this->redirectToOpenIdProvider($request);
        }

        $token = new OpenIdToken($this->getJwtToken($request));

        return $this->authenticationManager->authenticate($token);
    }

    private function isLoginPath(Request $request): bool
    {
        return $this->httpUtils->checkRequestPath($request, $this->options['login_path']);
    }

    private function redirectToOpenIdProvider(Request $request): RedirectResponse
    {
        try {
            $nonce = base64_encode(random_bytes(128));
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to generate nonce');
        }

        $session = $request->getSession();
        if (! $session) {
            throw new \RuntimeException('This authentication method requires a session.');
        }

        $session->set(self::NONCE_SESSION_ATTRIBUTE, $nonce);

        return new RedirectResponse(
            'http://login.dev-facile.it/oauth2/authorize?'
            . http_build_query([
                'response_type' => 'code id_token',
                'scope' => 'openid email profile groups',
                // TODO: parametrizzare il client id
                'client_id' => 'client_test',
                'nonce' => $nonce,
                'state' => $this->getState($session),
                'redirect_uri' => 'http://insight.dev-facile.it' . $this->options['check_path'],
                // $this->router->generate('facile_openid_check', [], RouterInterface::ABSOLUTE_URL),
            ])
        );
    }

    private function getJwtToken(Request $request): JWTToken
    {
        $stringToken = $request->get('id_token');

        return $this->jwtParser->parse($stringToken);
    }

    private function getState(SessionInterface $session): string
    {
        return sha1($session->getId());
    }
}
