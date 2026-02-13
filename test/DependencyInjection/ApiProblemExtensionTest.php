<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Phpro\ApiProblemBundle\DependencyInjection\ApiProblemExtension;
use Phpro\ApiProblemBundle\EventListener\JsonApiProblemExceptionListener;
use Phpro\ApiProblemBundle\Transformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Reference;

#[CoversClass(ApiProblemExtension::class)]
class ApiProblemExtensionTest extends AbstractExtensionTestCase
{
    private const TRANSFORMER_TAG = 'phpro.api_problem.exception_transformer';

    protected function getContainerExtensions(): array
    {
        return [new ApiProblemExtension()];
    }

    #[Test]
    public function it_registers_json_exception_listener(): void
    {
        $this->load([]);

        $this->assertContainerBuilderHasService(
            JsonApiProblemExceptionListener::class,
            JsonApiProblemExceptionListener::class
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            JsonApiProblemExceptionListener::class,
            '$exceptionTransformer',
            new Reference(Transformer\Chain::class),
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
                'priority' => '-5',
            ]
        );
    }

    #[Test]
    public function it_contains_exception_transformers(): void
    {
        $this->load([]);

        $this->assertContainerBuilderHasService(
            Transformer\Chain::class,
            Transformer\Chain::class
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            Transformer\Chain::class,
            0,
            new TaggedIteratorArgument(self::TRANSFORMER_TAG)
        );

        $this->assertContainerHasTransformer(Transformer\ApiProblemExceptionTransformer::class);
        $this->assertContainerHasTransformer(Transformer\HttpExceptionTransformer::class);
        $this->assertContainerHasTransformer(Transformer\SecurityExceptionTransformer::class);
    }

    private function assertContainerHasTransformer(string $serviceId): void
    {
        $this->assertContainerBuilderHasService($serviceId, $serviceId);
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, self::TRANSFORMER_TAG);
    }
}
