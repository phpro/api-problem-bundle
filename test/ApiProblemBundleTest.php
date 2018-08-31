<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle;

use Phpro\ApiProblemBundle\ApiProblemBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/** @covers \Phpro\ApiProblemBundle\ApiProblemBundle */
class ApiProblemBundleTest extends TestCase
{
    /** @test */
    public function it_is_a_symfony_bundle(): void
    {
        $this->assertInstanceOf(Bundle::class, new ApiProblemBundle());
    }
}
