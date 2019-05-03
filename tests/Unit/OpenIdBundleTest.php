<?php

namespace Facile\OpenIdBundle\Tests\Unit;

use Facile\OpenIdBundle\DependencyInjection\Security\Factory\OpenIdFactory;
use Facile\OpenIdBundle\OpenIdBundle;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class OpenIdBundleTest extends TestCase
{
    public function testBuild(): void
    {
        $securityExtension = $this->prophesize(SecurityExtension::class);
        $securityExtension->addSecurityListenerFactory(Argument::type(OpenIdFactory::class))
            ->shouldBeCalledTimes(1);

        $container = $this->prophesize(ContainerBuilder::class);
        $container->getExtension('security')
            ->shouldBeCalled()
            ->willReturn($securityExtension->reveal());
        
        
        $bundle = new OpenIdBundle();
        $bundle->build($container->reveal());
    }

    public function testBuildWithWrongExtension(): void
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $container->getExtension('security')
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ExtensionInterface::class));

        $bundle = new OpenIdBundle();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expecting SecurityExtension');

        $bundle->build($container->reveal());
    }
}
