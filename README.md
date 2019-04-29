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

Add the routes to your routing configuration:
```yaml
## app/config/routing.yml

facile_openid_login:
    resource: "@OpenIdBundle/Resources/config/routing/login.xml"
    # choose a prefix for the login routes to avoid collisions
    prefix: /openid 
```

Under the Security bundle configuration of your Symfony application, configure the firewall and the access control:

```yaml
security:
  # ...

  firewalls:
    my_secured_firewall:
      pattern: ^/secured # choose the right pattern to protect behind the OpenId authentication
      facile_openid:
        # add the service reference for the user provider
        user_provider: 'user_provider_service'

  # ...

  access_control:
  # use the same URL prefix that you chose in the routing.yaml 
  # to require no more than an anonymous session on those routes
  - { path: ^/openid, roles: IS_AUTHENTICATED_ANONYMOUSLY }
  # ...
```

The `user_provider` option must contain the ID of a service that implements the `Facile\OpenIdBundle\Security\UserProvider`
interface. That class is the hook point of this bundle, where you can choose which user is getting authenticated with the
provided (and validated) JWT token.

## Additional configuration

TODO

## Usage

TODO
