<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle;

use Phpro\ApiProblemBundle\ApiProblemBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

#[CoversClass(ApiProblemBundle::class)]
class ApiProblemBundleTest extends TestCase
{
    #[Test]
    public function it_is_a_symfony_bundle(): void
    {
        $this->assertInstanceOf(Bundle::class, new ApiProblemBundle());
    }
}
