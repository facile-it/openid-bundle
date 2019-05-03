<?php

declare(strict_types=1);

namespace Facile\OpenIdBundle\Tests\Integration;

use Facile\OpenIdBundle\Tests\App\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class BaseIntegrationTestCase extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return AppKernel::class;
    }
}
