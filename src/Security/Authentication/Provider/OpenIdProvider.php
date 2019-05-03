<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Security\Authentication\Provider;

use Facile\OpenIdBundle\Exception\AuthenticationException;
use Facile\OpenIdBundle\Security\Authentication\Token\OpenIdToken;
use Facile\OpenIdBundle\Security\Crypto;
use Facile\OpenIdBundle\Security\UserProvider;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OpenIdProvider implements AuthenticationProviderInterface
{
    /** @var UserProvider */
    private $userProvider;

    /** @var Crypto */
    private $crypto;

    /** @var LoggerInterface */
    private $logger;

    /** @var false|string */
    private $jwtPublicKey;

    public function __construct(UserProvider $userProvider, Crypto $crypto, ?LoggerInterface $logger, string $keyPath)
    {
        $this->userProvider = $userProvider;
        $this->crypto = $crypto;
        $this->logger = $logger ?? new NullLogger();
        $this->jwtPublicKey = file_get_contents($keyPath);
    }

    public function authenticate(TokenInterface $token)
    {
        if (! $token instanceof OpenIdToken) {
            throw new \InvalidArgumentException('Expecting OpenIdToken, got ' . \get_class($token));
        }

        if (! $this->isJwtTokenSignatureValid($token)) {
            $this->logger->error('Authentication failed: OpenId token signature is invalid');

            throw new AuthenticationException();
        }

        if ($this->crypto->getNonce() !== $token->getOpenIdToken()->getClaim('nonce')) {
            $this->logger->error('Authentication failed: OpenId token has invalid nonce');

            throw new AuthenticationException();
        }

        if ($user = $this->userProvider->findUserByToken($token)) {
            $authenticatedToken = new OpenIdToken($token->getOpenIdToken(), $user->getRoles());
            $authenticatedToken->setUser($user);
            $authenticatedToken->setAuthenticated(true);

            return $authenticatedToken;
        }

        $this->logger->error('Authentication failed: no suitable user provided');

        throw new AuthenticationException();
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof OpenIdToken;
    }

    private function isJwtTokenSignatureValid(OpenIdToken $token): bool
    {
        $openIdToken = $token->getOpenIdToken();

        if ('RS256' !== $alg = $openIdToken->getHeader('alg')) {
            $this->logger->critical('Unsupported JWT signature algorithm: ' . $alg);

            throw new AuthenticationException();
        }

        if (empty($this->jwtPublicKey)) {
            $this->logger->critical('JWT signing public key file is missing or empty, cannot verify OpenId token');

            throw new AuthenticationException();
        }

        try {
            return $openIdToken->verify(new Sha256(), $this->jwtPublicKey);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->critical('JWT signing public key is invalid, cannot verify OpenId token');

            throw new AuthenticationException();
        }
    }
}
