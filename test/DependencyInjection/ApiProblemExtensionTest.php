<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Phpro\ApiProblemBundle\DependencyInjection\ApiProblemExtension;
use Phpro\ApiProblemBundle\EventListener\JsonApiProblemExceptionListener;

/**
 * @covers \Phpro\ApiProblemBundle\DependencyInjection\ApiProblemExtension
 */
class ApiProblemExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [new ApiProblemExtension()];
    }

    /** @test */
    public function it_registers_json_exception_listener(): void
    {
        $this->load([]);

        $this->assertContainerBuilderHasService(
            JsonApiProblemExceptionListener::class,
            JsonApiProblemExceptionListener::class
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            JsonApiProblemExceptionListener::class,
            '$debug',
            '%kernel.debug%'
        );
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            JsonApiProblemExceptionListener::class,
            'kernel.event_listener',
            [
                'event' => 'kernel.exception',
                'method' => 'onKernelException',
            ]
        );
    }
}
