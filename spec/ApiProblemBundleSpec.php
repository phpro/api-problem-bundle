<?php

declare(strict_types=1);

namespace spec\Phpro\ApiProblemBundle;

use Phpro\ApiProblemBundle\ApiProblemBundle;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ApiProblemBundleSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ApiProblemBundle::class);
    }

    public function it_is_a_symfony_bundle(): void
    {
        $this->shouldHaveType(Bundle::class);
    }
}
