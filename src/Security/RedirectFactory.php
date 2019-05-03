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

    /** @var string[] */
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

        $parameters = [
            'response_type' => 'code id_token',
            'scope' => 'openid email profile groups',
            'client_id' => $this->options[OpenIdFactory::CLIENT_ID],
            'nonce' => $nonce,
            'state' => $this->crypto->getState(),
            'redirect_uri' => $this->router->generate(
                $this->options[OpenIdFactory::CHECK_PATH],
                [],
                RouterInterface::ABSOLUTE_URL
            ),
        ];

        return new RedirectResponse($this->options[OpenIdFactory::AUTH_ENDPOINT] . '?' . http_build_query($parameters));
    }
}
