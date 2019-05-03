# facile-it/openid-bundle

[![PHP Version](https://img.shields.io/badge/php-%5E7.1-blue.svg)](https://img.shields.io/badge/php-%5E7.1-blue.svg)
[![Stable release][Last stable image]][Packagist link]
[![Unstable release][Last unstable image]][Packagist link]

[![Build status][Master build image]][Master build link]
[![Coverage Status][Master coverage image]][Master coverage link]

This bundles add a new [custom authentication provider](https://symfony.com/doc/current/security/custom_authentication_provider.html) for your Symfony firewall, allowing authentication of your users using a third party OpenId provider.

## Installation

Require the package through Composer

```bash
composer require facile-it/openid-bundle
```

Add the bundle to your app kernel:

```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Facile\OpenIdBundle\OpenIdBundle(),
        ];

        // ...
```

## Configuration

Add the two needed routes to your routing configuration; names and paths are up to you:
```yaml
## app/config/routing.yml

facile_openid_login: # your login route, that will redirect your user to the OpenId service
    path: /openid/login

facile_openid_check: # your check route, where your user will return back for authentication on your app
    path: /openid/check
```

Define a service that implements the `\Facile\OpenIdBundle\Security\UserProvider` interface:
```php
<?php

namespace App\Security;

use Facile\OpenIdBundle\Security\Authentication\Token\OpenIdToken;
use Symfony\Component\Security\Core\User\UserInterface;

class MyOpenIdUserProvider implements \Facile\OpenIdBundle\Security\UserProvider
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
    public function findUserByToken(OpenIdToken $token): ?UserInterface
    {
        // ...
    }
}
```

Under the Security bundle configuration of your Symfony application, configure the firewall like this:

```yaml
security:
  # ...

  firewalls:
    my_secured_firewall:
      pattern: ^/(secured|openid) # choose the right pattern to protect behind the OpenId authentication 
      facile_openid:
        auth_endpoint: 'http://login.example.com/oauth2/authorize' # the endpoint of the OpenId service to redirect to for authentication 
        client_id: 'client_test' # your client ID
        login_path: facile_openid_login # the route name or path of your login route
        check_path: facile_openid_check # the route name or path of your check route
        jwt_key_path: '/some/path/to/jwt/public.key' # the file path to the public key that was used to sign the OpenId JWT token
        provider: App\Security\MyOpenIdUserProvider # the ID of the service implementing the UserProvider interface 
```
*NOTE*: the `login_path` & `check_path` routes must be matched by the pattern of this firewall, or othewise the firewall will not be triggered.

[Last stable image]: https://poser.pugx.org/facile-it/openid-bundle/version.svg
[Last unstable image]: https://poser.pugx.org/facile-it/openid-bundle/v/unstable.svg
[Master build image]: https://travis-ci.org/facile-it/openid-bundle.svg
[Master coverage image]: https://coveralls.io/repos/facile-it/openid-bundle/badge.svg?branch=master&service=github

[Packagist link]: https://packagist.org/packages/facile-it/openid-bundle
[Master build link]: https://travis-ci.org/facile-it/openid-bundle
[Master coverage link]: https://coveralls.io/github/facile-it/openid-bundle?branch=master
