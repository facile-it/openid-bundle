<?php

declare(strict_types=1);

namespace Facile\LoginBundle\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException as SymfonyAuthenticationException;

class AuthenticationException extends SymfonyAuthenticationException
{
    public function __construct()
    {
        parent::__construct('OpenId authentication failed');
    }
}
