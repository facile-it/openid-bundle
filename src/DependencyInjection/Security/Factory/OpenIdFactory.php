<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\DependencyInjection\Security\Factory;

use Facile\OpenIdBundle\Security\Authentication\Provider\OpenIdProvider;
use Facile\OpenIdBundle\Security\Firewall\OpenIdListener;
use Facile\OpenIdBundle\Security\RedirectFactory;
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
    public const AUTH_ENDPOINT = 'auth_endpoint';
    public const CLIENT_ID = 'client_id';
    public const USER_PROVIDER_SERVICE = 'provider';
    public const LOGIN_PATH = 'login_path';
    public const CHECK_PATH = 'check_path';
    public const JWT_KEY_PATH = 'jwt_key_path';
    public const SCOPE = 'scope';

    private const REQUIRED_OPTIONS = [
        self::AUTH_ENDPOINT,
        self::CLIENT_ID,
        self::USER_PROVIDER_SERVICE,
        self::LOGIN_PATH,
        self::CHECK_PATH,
        self::JWT_KEY_PATH,
        self::SCOPE,
    ];

    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPoint)
    {
        foreach (self::REQUIRED_OPTIONS as $option) {
            if (empty($config[$option])) {
                throw new \InvalidArgumentException(sprintf('Missing option %s in firewall %s', $option, $id));
            }
        }

        $userProvider = new Reference($userProviderId);

        $providerId = 'security.authentication.provider.facile_openid.' . $id;
        $container->setDefinition($providerId, $this->createProviderDefinition($userProvider, $config));

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

        $childNodes->scalarNode(self::AUTH_ENDPOINT);
        $childNodes->scalarNode(self::CLIENT_ID);
        $childNodes->scalarNode(self::USER_PROVIDER_SERVICE);
        $childNodes->scalarNode(self::LOGIN_PATH);
        $childNodes->scalarNode(self::CHECK_PATH);
        $childNodes->scalarNode(self::JWT_KEY_PATH);
        $childNodes->arrayNode(self::SCOPE)
            ->scalarPrototype()
            ->cannotBeEmpty()
            ->end()
            ->defaultValue(['email'])
        ;
    }

    private function createProviderDefinition(Reference $userProvider, array $config): Definition
    {
        $logger = new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE);

        return new Definition(OpenIdProvider::class, [
            $userProvider,
            new Reference('facile_openid.crypto'),
            $logger,
            $config[self::JWT_KEY_PATH],
        ]);
    }

    private function createListenerDefinition(array $config, string $id): Definition
    {
        $httpUtils = new Reference('facile_openid.http_utils');
        $logger = new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        $config['require_previous_session'] = false;

        $definition = new Definition(OpenIdListener::class);
        $definition->setArguments([
            new Reference('facile_openid.jwt_parser'),
            $this->createRedirectFactoryDefinition($config),
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

    private function createRedirectFactoryDefinition(array $config): Definition
    {
        return new Definition(RedirectFactory::class, [
            new Reference('router'),
            new Reference('facile_openid.crypto'),
            $config,
        ]);
    }
}
