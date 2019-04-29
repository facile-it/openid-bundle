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

## Configuration

TODO

## Usage

TODO
