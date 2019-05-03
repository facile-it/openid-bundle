<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Tests\App\Controller;

use Symfony\Component\HttpFoundation\Response;

class UnsecuredController
{
    public function index(): Response
    {
        return new Response('This is an unsecured controller');
    }
}
