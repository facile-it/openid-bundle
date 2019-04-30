<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\DependencyInjection\Security\Factory;

use Facile\OpenIdBundle\Security\Authentication\Provider\OpenIdProvider;
use Facile\OpenIdBundle\Security\Firewall\OpenIdListener;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;

class OpenIdFactory implements SecurityFactoryInterface
{
    private const AUTH_ENDPOINT = 'auth_endpoint';
    private const USER_PROVIDER_SERVICE = 'user_provider';
    private const LOGIN_PATH = 'login_path';
    private const CHECK_PATH = 'check_path';
    private const JWT_KEY_PATH = 'jwt_key_path';

    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.facile_openid.' . $id;
        $container->setDefinition($providerId, $this->createProviderDefinition($config));

        $listenerId = 'security.authentication.listener.facile_openid.' . $id;
        $container->setDefinition($listenerId, $this->createListenerDefinition($config, $id));

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

    public function addConfiguration(NodeDefinition $node)
    {
        if (! $node instanceof ArrayNodeDefinition) {
            throw new \InvalidArgumentException('Unable to build child nodes: expecting ArrayNodeDefinition, got ' . \get_class($node));
        }

        $childNodes = $node->children();

        $childNodes->scalarNode(self::AUTH_ENDPOINT)
            ->cannotBeEmpty();
        $childNodes->scalarNode(self::USER_PROVIDER_SERVICE)
            ->cannotBeEmpty();
        $childNodes->scalarNode(self::LOGIN_PATH)
            ->cannotBeEmpty();
        $childNodes->scalarNode(self::CHECK_PATH)
            ->cannotBeEmpty();
        $childNodes->scalarNode(self::JWT_KEY_PATH)
            ->cannotBeEmpty();
    }

    private function createProviderDefinition(array $config): Definition
    {
        $userProvider = new Reference($config[self::USER_PROVIDER_SERVICE]);
        $logger = new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE);

        return new Definition(OpenIdProvider::class, [$userProvider, $logger, $config[self::JWT_KEY_PATH]]);
    }

    private function createListenerDefinition(array $config, string $id): Definition
    {
        $httpUtils = new Reference('facile_openid.http_utils');
        $logger = new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE);

        $definition = new Definition(OpenIdListener::class);
        $definition->setArguments([
            new Reference('facile_openid.jwt_parser'),
            new Reference('security.token_storage'),
            new Reference('security.authentication.manager'),
            new Reference('security.authentication.session_strategy'),
            $httpUtils,
            'facile_openid.provider_key.' . $id,
            new Definition(DefaultAuthenticationSuccessHandler::class, [$httpUtils, [/* TODO: config */]]),
            new Definition(DefaultAuthenticationFailureHandler::class, [new Reference('kernel'), $httpUtils, [/* TODO: config */], $logger]),
            $config,
            $logger,
            new Reference('event_dispatcher', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

        return $definition;
    }
}
