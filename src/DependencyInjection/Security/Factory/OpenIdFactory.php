<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\DependencyInjection\Security\Factory;

use Facile\OpenIdBundle\Security\Authentication\Provider\OpenIdProvider;
use Facile\OpenIdBundle\Security\Firewall\OpenIdListener;
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
            ->setDefinition($providerId, new ChildDefinition(OpenIdProvider::class))
            ->setArgument(0, new Reference($userProvider))
        ;

        $listenerId = 'security.authentication.listener.facile_openid.' . $id;
        $container->setDefinition($listenerId, new ChildDefinition(OpenIdListener::class));

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
