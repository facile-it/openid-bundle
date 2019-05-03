<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Tests\Unit\Security;

use Facile\OpenIdBundle\Security\Crypto;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CryptoTest extends TestCase
{
    public function testGetNonce(): void
    {
        $session = $this->prophesize(SessionInterface::class);
        $session->set('facile-openid-nonce', Argument::type('string'));

        $crypto = new Crypto($session->reveal());

        $generateNonce = $crypto->generateNonce();

        $session->get('facile-openid-nonce')
            ->shouldBeCalled()
            ->willReturn($generateNonce);

        $this->assertSame($generateNonce, $crypto->getNonce());
    }

    public function testGetNonceWithNoPreviousGeneration(): void
    {
        $session = $this->prophesize(SessionInterface::class);

        $crypto = new Crypto($session->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to retrieve nonce');

        $crypto->getNonce();
    }

    public function testGetState(): void
    {
        $session = $this->prophesize(SessionInterface::class);
        $session->getId()
            ->willReturn('session_id');

        $crypto = new Crypto($session->reveal());

        $this->assertSame(sha1('session_id'), $crypto->getState());
    }
}
