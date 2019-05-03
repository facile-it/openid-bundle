<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Tests\Integration;

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

        $client->request('GET', '/secured/login');

        $response = $client->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(302, $response->getStatusCode());
        $locationHeader = $response->headers->get('Location');
        $this->assertStringStartsWith('https://login.openid.dev/oauth2/authorize', $locationHeader);
    }
}
