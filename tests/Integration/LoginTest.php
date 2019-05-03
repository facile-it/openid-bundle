<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Tests\Integration;

use Facile\OpenIdBundle\Tests\App\AppKernel;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token as JWTToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class LoginTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return AppKernel::class;
    }

    public function testUnsecuredRoute(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/unsecured');

        $this->assertStringContainsString('unsecured', $crawler->text());
    }

    public function testRedirectToOpenIdProvider(): void
    {
        $client = static::createClient();

        $this->expectException(HttpException::class);

        try {
            $client->request('GET', '/secured/index');
        } catch (\Throwable $exception) {
            $rootException = $exception;
            do {
                $rootException = $rootException->getPrevious();
            } while ($rootException->getPrevious());

            $this->assertInstanceOf(AccessDeniedException::class, $rootException);

            throw $exception;
        }
    }

    public function testFullLoginFlow(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        // request login_path route
        $client->request('GET', '/secured/login');

        $response = $client->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(302, $response->getStatusCode());
        $locationHeader = $response->headers->get('Location');
        $this->assertStringStartsWith('https://login.openid.dev/oauth2/authorize?', $locationHeader);

        $queryString = substr($locationHeader, strlen('https://login.openid.dev/oauth2/authorize?'));
        parse_str($queryString, $redirectParameters);

        $this->assertArrayHasKey('response_type', $redirectParameters);
        $this->assertArrayHasKey('scope', $redirectParameters);
        $this->assertArrayHasKey('client_id', $redirectParameters);
        $this->assertArrayHasKey('nonce', $redirectParameters);
        $this->assertArrayHasKey('state', $redirectParameters);
        $this->assertArrayHasKey('redirect_uri', $redirectParameters);

        $this->assertStringEndsWith('/secured/check', $redirectParameters['redirect_uri']);

        // return to check_path route with valid token
        $jwtToken = $this->prepareValidJwtToken($redirectParameters['nonce']);

        $client->request('GET', '/secured/check', [
            'id_token' => $jwtToken->__toString(),
        ]);

        $response = $client->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(302, $response->getStatusCode());
        $locationHeader = $response->headers->get('Location');
        $this->assertSame('http://localhost/', $locationHeader);
    }

    private function prepareValidJwtToken(string $nonce): JWTToken
    {
        $builder = new Builder();

        $builder->setIssuer('https://login.openid.dev');
        $builder->setAudience('http://localhost');
        $builder->setId('4f1g23a12aa', true);
        $builder->setIssuedAt(time());

        $builder->set('uid', 1);
        $builder->set('nonce', $nonce);

        $this->signJwtToken($builder);

        return $builder->getToken();
    }

    private function signJwtToken(Builder $builder): void
    {
        $privateKeyFile = dirname(__DIR__) . '/App/jwt/private.key';
        $this->assertFileExists($privateKeyFile, 'Missing signing private key');

        $signingPrivateKey = file_get_contents($privateKeyFile);
        $this->assertNotFalse($signingPrivateKey);

        $builder->sign(new Sha256(), $signingPrivateKey);
    }
}
