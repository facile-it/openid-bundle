<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Tests\Integration;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class LoginTest extends BaseIntegrationTestCase
{
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
            $this->assertInstanceOf(InsufficientAuthenticationException::class, $exception->getPrevious());

            throw $exception;
        }
    }

    public function testFullLoginFlow(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

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
    }
}
