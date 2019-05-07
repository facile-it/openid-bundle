<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Security;

use Facile\OpenIdBundle\DependencyInjection\Security\Factory\OpenIdFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class RedirectFactory
{
    /** @var RouterInterface */
    private $router;

    /** @var Crypto */
    private $crypto;

    /** @var array */
    private $options;

    /**
     * RedirectFactory constructor.
     *
     * @param string[] $options
     */
    public function __construct(RouterInterface $router, Crypto $crypto, array $options)
    {
        $this->router = $router;
        $this->crypto = $crypto;
        $this->options = $options;
    }

    public function toOpenIdLogin(): RedirectResponse
    {
        $nonce = $this->crypto->generateNonce();

        $scopes = $this->options[OpenIdFactory::SCOPE];
        if (! is_array($scopes)) {
            throw new \InvalidArgumentException('Expecting array of string, got ' . gettype($scopes));
        }

        array_unshift($scopes, 'openid');

        $parameters = [
            'response_type' => 'code id_token',
            'scope' => implode(' ', array_unique($scopes)),
            'client_id' => $this->options[OpenIdFactory::CLIENT_ID],
            'nonce' => $nonce,
            'state' => $this->crypto->getState(),
            'redirect_uri' => $this->getRedirectUri(),
        ];

        return new RedirectResponse($this->options[OpenIdFactory::AUTH_ENDPOINT] . '?' . http_build_query($parameters));
    }

    private function getRedirectUri(): string
    {
        $checkPath = $this->options[OpenIdFactory::CHECK_PATH];

        if ($this->router->getRouteCollection()->get($checkPath)) {
            return $this->router->generate($checkPath, [], RouterInterface::ABSOLUTE_URL);
        }

        return $this->router->getContext()->getBaseUrl() . $checkPath;
    }
}
