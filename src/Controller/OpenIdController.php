<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Controller;

use Facile\OpenIdBundle\Exception\AuthenticationException;
use Lcobucci\JWT\Parser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

class OpenIdController
{
    private const NONCE_SESSION_ATTRIBUTE = 'facile-openid-nonce';

    /** @var SessionInterface */
    private $session;

    /** @var RouterInterface */
    private $router;

    /**
     * OpenIdController constructor.
     */
    public function __construct(SessionInterface $session, RouterInterface $router)
    {
        $this->session = $session;
        $this->router = $router;
    }

    public function login(): RedirectResponse
    {
        try {
            $nonce = base64_encode(random_bytes(128));
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to generate nonce');
        }

        $this->session->set(self::NONCE_SESSION_ATTRIBUTE, $nonce);

        return new RedirectResponse(
            'http://login.dev-facile.it/oauth2/authorize?'
            . http_build_query([
                'response_type' => 'code id_token',
                'scope' => 'openid email profile groups',
                'client_id' => 'client_test', // TODO
                'nonce' => $nonce,
                'state' => $this->getState(),
                'redirect_uri' => $this->router->generate('facile_openid_check', [], RouterInterface::ABSOLUTE_URL),
            ])
        );
    }

    public function check(Request $request)
    {
        $jwtToken = $request->get('id_token');

        $token = (new Parser())->parse($jwtToken);

        // TODO -- verifica firma token JWT
//        $token->verify($signer);

        dump($token->getHeaders());
        dump(json_decode(json_encode($token->getClaims()), true));

        dump($request->get('state') !== $this->getState());
        dump($token->getClaims()['nonce'] !== $this->session->get(self::NONCE_SESSION_ATTRIBUTE));

        die();

        if ($request->get('state') !== $this->getState()) {
            throw new AuthenticationException();
        }

        if ($token->getClaims()['nonce'] !== $this->session->get(self::NONCE_SESSION_ATTRIBUTE)) {
            throw new AuthenticationException();
        }
    }

    private function getState(): string
    {
        return sha1($this->session->getId());
    }
}
