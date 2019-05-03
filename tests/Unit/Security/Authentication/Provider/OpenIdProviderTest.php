<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Tests\Unit\Security\Authentication\Provider;

use Facile\OpenIdBundle\Exception\AuthenticationException;
use Facile\OpenIdBundle\Security\Authentication\Provider\OpenIdProvider;
use Facile\OpenIdBundle\Security\Authentication\Token\OpenIdToken;
use Facile\OpenIdBundle\Security\Crypto;
use Facile\OpenIdBundle\Security\UserProvider;
use Lcobucci\JWT\Builder as JwtBuilder;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token as JwtToken;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class OpenIdProviderTest extends TestCase
{
    public function testAuthenticateWithWrongToken(): void
    {
        $openIdProvider = new OpenIdProvider(
            $this->prophesize(UserProvider::class)->reveal(),
            $this->prophesize(Crypto::class)->reveal(),
            new NullLogger(),
            $this->getPublicKeyPath()
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expecting OpenIdToken');

        $openIdProvider->authenticate($this->prophesize(TokenInterface::class)->reveal());
    }

    public function testAuthenticateWithUnsupportedSignatureAlgorithm(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);

        $openIdProvider = new OpenIdProvider(
            $this->prophesize(UserProvider::class)->reveal(),
            $this->prophesize(Crypto::class)->reveal(),
            $logger->reveal(),
            $this->getPublicKeyPath()
        );

        $logger->critical(Argument::containingString('Unsupported JWT signature algorithm'))
            ->shouldBeCalledOnce();

        $this->expectException(AuthenticationException::class);

        $openIdProvider->authenticate(new OpenIdToken((new JwtBuilder())->getToken()));
    }

    public function testAuthenticateWithMissingPublicKey(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $emptyFile = new \SplTempFileObject();

        $openIdProvider = new OpenIdProvider(
            $this->prophesize(UserProvider::class)->reveal(),
            $this->prophesize(Crypto::class)->reveal(),
            $logger->reveal(),
            $emptyFile->getPathname()
        );

        $jwtToken = (new JwtBuilder())
            ->sign(new Sha256(), file_get_contents($this->getPrivateKeyPath()))
            ->getToken();

        $logger->critical('JWT signing public key file is missing or empty, cannot verify OpenId token')
            ->shouldBeCalledOnce();

        $this->expectException(AuthenticationException::class);

        $openIdProvider->authenticate(new OpenIdToken($jwtToken));
    }

    public function testAuthenticateWithInvalidPublicKey(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);

        $openIdProvider = new OpenIdProvider(
            $this->prophesize(UserProvider::class)->reveal(),
            $this->prophesize(Crypto::class)->reveal(),
            $logger->reveal(),
            __FILE__
        );

        $jwtToken = (new JwtBuilder())
            ->sign(new Sha256(), file_get_contents($this->getPrivateKeyPath()))
            ->getToken();

        $logger->critical('JWT signing public key is invalid, cannot verify OpenId token')
            ->shouldBeCalledOnce();

        $this->expectException(AuthenticationException::class);

        $openIdProvider->authenticate(new OpenIdToken($jwtToken));
    }

    public function testAuthenticateWithInvalidSignature(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);

        $openIdProvider = new OpenIdProvider(
            $this->prophesize(UserProvider::class)->reveal(),
            $this->prophesize(Crypto::class)->reveal(),
            $logger->reveal(),
            __FILE__
        );

        $jwtToken = $this->prophesize(JwtToken::class);
        $jwtToken->getHeader('alg')
            ->willReturn('RS256');
        $jwtToken->verify(Argument::type(Sha256::class), Argument::cetera())
            ->willReturn(false);

        $logger->error('Authentication failed: OpenId token signature is invalid')
            ->shouldBeCalledOnce();

        $this->expectException(AuthenticationException::class);

        $openIdProvider->authenticate(new OpenIdToken($jwtToken->reveal()));
    }

    public function testAuthenticateWithInvalidNonce(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $crypto = $this->prophesize(Crypto::class);

        $openIdProvider = new OpenIdProvider(
            $this->prophesize(UserProvider::class)->reveal(),
            $crypto->reveal(),
            $logger->reveal(),
            __FILE__
        );

        $jwtToken = $this->prophesize(JwtToken::class);
        $jwtToken->getHeader('alg')
            ->willReturn('RS256');
        $jwtToken->verify(Argument::type(Sha256::class), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(true);
        $jwtToken->getClaim('nonce')
            ->willReturn('different_nonce');
        $crypto->getNonce()
            ->willReturn('current_nonce');

        $logger->error('Authentication failed: OpenId token has invalid nonce')
            ->shouldBeCalledOnce();

        $this->expectException(AuthenticationException::class);

        $openIdProvider->authenticate(new OpenIdToken($jwtToken->reveal()));
    }

    public function testAuthenticateWithNoUserProvided(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $crypto = $this->prophesize(Crypto::class);
        $userProvider = $this->prophesize(UserProvider::class);

        $openIdProvider = new OpenIdProvider(
            $userProvider->reveal(),
            $crypto->reveal(),
            $logger->reveal(),
            __FILE__
        );

        $jwtToken = $this->prophesize(JwtToken::class);
        $jwtToken->getHeader('alg')
            ->willReturn('RS256');
        $jwtToken->verify(Argument::type(Sha256::class), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(true);
        $jwtToken->getClaim('nonce')
            ->willReturn('current_nonce');
        $crypto->getNonce()
            ->willReturn('current_nonce');
        $userProvider->findUserByToken(Argument::type(OpenIdToken::class))
            ->willReturn(null);

        $logger->error('Authentication failed: no suitable user provided')
            ->shouldBeCalledOnce();

        $this->expectException(AuthenticationException::class);

        $openIdProvider->authenticate(new OpenIdToken($jwtToken->reveal()));
    }

    public function testAuthenticateWithSuccess(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $crypto = $this->prophesize(Crypto::class);
        $userProvider = $this->prophesize(UserProvider::class);

        $openIdProvider = new OpenIdProvider(
            $userProvider->reveal(),
            $crypto->reveal(),
            $logger->reveal(),
            __FILE__
        );

        $jwtToken = $this->prophesize(JwtToken::class);
        $jwtToken->getHeader('alg')
            ->willReturn('RS256');
        $jwtToken->verify(Argument::type(Sha256::class), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(true);
        $jwtToken->getClaim('nonce')
            ->willReturn('current_nonce');
        $crypto->getNonce()
            ->willReturn('current_nonce');

        $user = $this->prophesize(UserInterface::class);
        $user->getRoles()
            ->willReturn(['ROLE_ONE', 'ROLE_TWO']);
        $userProvider->findUserByToken(Argument::type(OpenIdToken::class))
            ->willReturn($user->reveal());

        $unauthenticatedToken = new OpenIdToken($jwtToken->reveal());

        $authenticatedToken = $openIdProvider->authenticate($unauthenticatedToken);

        $this->assertNotSame($unauthenticatedToken, $authenticatedToken);
        $this->assertInstanceOf(OpenIdToken::class, $authenticatedToken);
        $this->assertTrue($authenticatedToken->isAuthenticated());
        $this->assertSame($user->reveal(), $authenticatedToken->getUser());
        $this->assertCount(2, $authenticatedToken->getRoles());
    }

    private function getPrivateKeyPath(): string
    {
        return dirname(__DIR__, 4) . '/App/jwt/private.key';
    }

    private function getPublicKeyPath(): string
    {
        return dirname(__DIR__, 4) . '/App/jwt/public.key';
    }
}
