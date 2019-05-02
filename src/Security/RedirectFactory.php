<?php

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

    /** @var string[] */
    private $options;

    /**
     * RedirectFactory constructor.
     * @param RouterInterface $router
     * @param Crypto $crypto
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

        return new RedirectResponse(
            'http://login.dev-facile.it/oauth2/authorize?'
            . http_build_query([
                'response_type' => 'code id_token',
                'scope' => 'openid email profile groups',
                // TODO: parametrizzare il client id
                'client_id' => 'client_test',
                'nonce' => $nonce,
                'state' => $this->crypto->getState(),
                'redirect_uri' => $this->router->generate($this->options[OpenIdFactory::CHECK_PATH], [], RouterInterface::ABSOLUTE_URL),
            ])
        );
    }
}
