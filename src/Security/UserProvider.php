<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Security;

use Facile\OpenIdBundle\Security\Authentication\Token\OpenIdToken;
use Symfony\Component\Security\Core\User\UserInterface;

interface UserProvider
{
    /**
     * Authentication hook point for the entire bundle.
     *
     * During the authentication procedure, this method is called to identify the user to be
     * authenticated in the current session. This method will hold all the logic to associate
     * the given OpenId token to an user of the current application. The user can even be
     * instantiated (and/or persisted) on the fly, and it will be set in the current session
     * afterwards.
     *
     * @param OpenIdToken $token the token obtained during the post-authentication redirect
     *
     * @return UserInterface|null the user associated to that token, or null if no user is found
     */
    public function findUserByToken(OpenIdToken $token): ?UserInterface;
}
