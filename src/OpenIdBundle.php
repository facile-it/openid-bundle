<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle;

use Facile\OpenIdBundle\DependencyInjection\Security\Factory\OpenIdFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OpenIdBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        
        $extension = $container->getExtension('security');
        if (! $extension instanceof SecurityExtension) {
            throw new \InvalidArgumentException('Expecting SecurityExtension, got ' . \get_class($extension));
        }
        
        $extension->addSecurityListenerFactory(new OpenIdFactory());
    }
}
