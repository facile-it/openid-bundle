<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Security\Authentication\Token;

use Lcobucci\JWT\Token as JWTToken;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class OpenIdToken extends AbstractToken
{
    /** @var JWTToken */
    private $openIdToken;

    public function __construct(JWTToken $openIdToken, array $roles = [])
    {
        parent::__construct($roles);

        $this->openIdToken = $openIdToken;
    }

    public function getOpenIdToken(): JWTToken
    {
        return $this->openIdToken;
    }

    public function getCredentials(): string
    {
        return $this->openIdToken->__toString();
    }
}
