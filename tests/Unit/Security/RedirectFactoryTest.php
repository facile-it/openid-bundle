<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Tests\Unit\Security;

use Facile\OpenIdBundle\DependencyInjection\Security\Factory\OpenIdFactory;
use Facile\OpenIdBundle\Security\Crypto;
use Facile\OpenIdBundle\Security\RedirectFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RedirectFactoryTest extends TestCase
{
    public function testToOpenIdLoginWithCheckRouteName(): void
    {
        $checkPath = 'check_path_route';

        $router = $this->prophesize(RouterInterface::class);
        $routeCollection = $this->prophesize(RouteCollection::class);

        $router->getRouteCollection()
            ->willReturn($routeCollection->reveal());
        $routeCollection->get($checkPath)
            ->willReturn($this->prophesize(Route::class));
        $router->generate($checkPath, [], RouterInterface::ABSOLUTE_URL)
            ->shouldBeCalled()
            ->willReturn('http://localhost/check');

        $redirectParameters = $this->executeTest($router->reveal(), $checkPath);

        $this->assertSame('openid email', $redirectParameters['scope']);
    }

    public function testToOpenIdLoginWithCheckPath(): void
    {
        $checkPath = '/check';

        $router = $this->prophesize(RouterInterface::class);
        $routeCollection = $this->prophesize(RouteCollection::class);
        $requestContext = $this->prophesize(RequestContext::class);

        $router->getRouteCollection()
            ->willReturn($routeCollection->reveal());
        $routeCollection->get($checkPath)
            ->willReturn(null);
        $router->getContext()
            ->willReturn($requestContext->reveal());
        $requestContext->getBaseUrl()
            ->willReturn('http://localhost');

        $redirectParameters = $this->executeTest($router->reveal(), $checkPath);

        $this->assertSame('openid email', $redirectParameters['scope']);
    }

    /**
     * @dataProvider scopesProvider
     *
     * @param $scopes
     */
    public function testToOpenIdLoginWithCustomScope(array $scopes, string $expectedScope): void
    {
        $checkPath = '/check';

        $router = $this->prophesize(RouterInterface::class);
        $routeCollection = $this->prophesize(RouteCollection::class);
        $requestContext = $this->prophesize(RequestContext::class);

        $router->getRouteCollection()
            ->willReturn($routeCollection->reveal());
        $routeCollection->get($checkPath)
            ->willReturn(null);
        $router->getContext()
            ->willReturn($requestContext->reveal());
        $requestContext->getBaseUrl()
            ->willReturn('http://localhost');

        $redirectParameters = $this->executeTest($router->reveal(), $checkPath, $scopes);

        $this->assertSame($expectedScope, $redirectParameters['scope']);
    }

    public function scopesProvider(): array
    {
        return [
            [['email', 'profile', 'groups'], 'openid email profile groups'],
            [['email', 'groups', 'openid'], 'openid email groups'],
            [['openid'], 'openid'],
            [[], 'openid email'],
        ];
    }

    private function executeTest(RouterInterface $router, string $checkPath, ?array $scopes = null): array
    {
        $options = [
            OpenIdFactory::AUTH_ENDPOINT => 'https://openid.dev/endpoint',
            OpenIdFactory::CLIENT_ID => 'test_client_id',
            OpenIdFactory::CHECK_PATH => $checkPath,
            OpenIdFactory::SCOPE => ['email'],
        ];

        if ($scopes) {
            $options[OpenIdFactory::SCOPE] = $scopes;
        }

        $crypto = $this->prophesize(Crypto::class);
        $crypto->generateNonce()
            ->shouldBeCalled()
            ->willReturn('generated_nonce');
        $crypto->getState()
            ->shouldBeCalled()
            ->willReturn('fetched_state');

        $factory = new RedirectFactory(
            $router,
            $crypto->reveal(),
            $options
        );

        $redirect = $factory->toOpenIdLogin();

        $this->assertSame(302, $redirect->getStatusCode());
        $target = $redirect->getTargetUrl();
        $this->assertStringStartsWith('https://openid.dev/endpoint?', $target);

        $queryString = substr($target, strlen('https://openid.dev/endpoint?'));
        parse_str($queryString, $redirectParameters);

        $this->assertSame('code id_token', $redirectParameters['response_type']);
        $this->assertSame($options[OpenIdFactory::CLIENT_ID], $redirectParameters['client_id']);
        $this->assertSame('generated_nonce', $redirectParameters['nonce']);
        $this->assertSame('fetched_state', $redirectParameters['state']);
        $this->assertSame('http://localhost/check', $redirectParameters['redirect_uri']);

        return $redirectParameters;
    }
}
