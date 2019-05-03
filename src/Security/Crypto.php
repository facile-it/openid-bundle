<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Security;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * This class provides the small bits of cryptography needed to handle the
 * OpenId authentication, nominally a nonce to be stored in the session and
 * a "state" string, fixed per user session.
 */
class Crypto
{
    private const NONCE_SESSION_ATTRIBUTE = 'facile-openid-nonce';

    /** @var SessionInterface */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @throws \RuntimeException When the nonce is not generated correctly
     */
    public function generateNonce(): string
    {
        try {
            $nonce = base64_encode(random_bytes(128));
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to generate nonce');
        }

        $this->session->set(self::NONCE_SESSION_ATTRIBUTE, $nonce);

        return $nonce;
    }

    /**
     * @throws \RuntimeException When the nonce is not retrieved
     */
    public function getNonce(): string
    {
        $nonce = $this->session->get(self::NONCE_SESSION_ATTRIBUTE);

        if (empty($nonce)) {
            throw new \RuntimeException('Unable to retrieve nonce');
        }

        return $nonce;
    }

    public function getState(): string
    {
        return sha1($this->session->getId());
    }
}
