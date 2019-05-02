# OpenIdBundle

## Installation

Require the package through Composer

```bash
TODO
```

Add the bundle to your kernel:

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

facile_openid_check: # your check route, where your user will return to for authentication on your app
    path: /openid/check
```

Define a service that implements the `\Facile\OpenIdBundle\Security\UserProvider` interface:
```php
<?php

declare(strict_types=1);

namespace App\Security;

use Facile\OpenIdBundle\Security\Authentication\Token\OpenIdToken;
use Symfony\Component\Security\Core\User\UserInterface;

class MyOpenIdUserProvider implements Facile\OpenIdBundle\Security\UserProvider
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
      pattern: ^/secured # choose the right pattern to protect behind the OpenId authentication
      facile_openid:
        auth_endpoint: 'http://login.example.com/oauth2/authorize' # the endpoint of the OpenId service to redirect to for authentication 
        client_id: 'client_test' # your client ID
        login_path: facile_openid_login # the route name or path of your login route
        check_path: facile_openid_check # the route name or path of your check route
        jwt_key_path: '/home/insight/jwt/public.key' # the file path to the public key that was used to sign the OpenId JWT token
        provider: App\Security\MyOpenIdUserProvider # the ID of the service implementing the UserProvider interface 
```
