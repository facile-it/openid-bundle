<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Security\Firewall;

use Facile\OpenIdBundle\DependencyInjection\Security\Factory\OpenIdFactory;
use Facile\OpenIdBundle\Security\RedirectFactory;
use Facile\OpenIdBundle\Security\Authentication\Token\OpenIdToken;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token as JWTToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

final class OpenIdListener extends AbstractAuthenticationListener
{
    /** @var Parser */
    private $jwtParser;

    /** @var RedirectFactory */
    private $redirectFactory;

    public function __construct(
        Parser $jwtParser,
        RedirectFactory $redirectFactory,
        // other deps from the abstract class below
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
        $this->redirectFactory = $redirectFactory;

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
        if ($this->isLoginRoute($request)) {
            return true;
        }

        return parent::requiresAuthentication($request);
    }

    protected function attemptAuthentication(Request $request)
    {
        if ($this->isLoginRoute($request)) {
            return $this->redirectFactory->toOpenIdLogin();
        }

        $token = new OpenIdToken($this->getJwtToken($request));

        return $this->authenticationManager->authenticate($token);
    }

    private function isLoginRoute(Request $request): bool
    {
        return $this->httpUtils->checkRequestPath($request, $this->options[OpenIdFactory::LOGIN_PATH]);
    }

    private function getJwtToken(Request $request): JWTToken
    {
        $stringToken = $request->get('id_token');

        return $this->jwtParser->parse($stringToken);
    }
}
