<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Tests\App\Security;

use Facile\OpenIdBundle\Security\Authentication\Token\OpenIdToken;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProvider implements \Facile\OpenIdBundle\Security\UserProvider
{
    public function findUserByToken(OpenIdToken $token): ?UserInterface
    {
        return new User('logged_in_user', 'no-password');
    }
}
