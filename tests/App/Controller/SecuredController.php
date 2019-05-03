<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Tests\App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecuredController
{
    public function index(Request $request): Response
    {
        if ($request->getUser()) {
            return new Response('This is an secured controller; current user ' . $request->getUser());
        }

        throw new \RuntimeException('No user detected');
    }
}
