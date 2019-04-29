<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OpenIdFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.facile_openid.' . $id;
        $container
            ->setDefinition($providerId, new ChildDefinition('facile_openid.openid_provider'))
            ->setArgument(0, new Reference($userProvider))
        ;

        $listenerId = 'security.authentication.listener.facile_openid.' . $id;
        $container->setDefinition($listenerId, new ChildDefinition('facile_openid.openid_listener'));

        return [$providerId, $listenerId, $defaultEntryPoint];
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'facile_openid';
    }

    public function addConfiguration(NodeDefinition $builder)
    {
    }
}
