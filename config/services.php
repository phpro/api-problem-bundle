<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Phpro\ApiProblemBundle\EventListener\JsonApiProblemExceptionListener;
use Phpro\ApiProblemBundle\Transformer\ApiProblemExceptionTransformer;
use Phpro\ApiProblemBundle\Transformer\Chain;
use Phpro\ApiProblemBundle\Transformer\HttpExceptionTransformer;
use Phpro\ApiProblemBundle\Transformer\SecurityExceptionTransformer;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(JsonApiProblemExceptionListener::class)
        ->class(JsonApiProblemExceptionListener::class)
        ->args([
            '$exceptionTransformer' => service(Chain::class),
            '$debug' => param('kernel.debug'),
        ])
        ->tag('kernel.event_listener', [
            'event' => 'kernel.exception',
            'method' => 'onKernelException',
            'priority' => -5,
        ]);

    $services->set(Chain::class)
        ->class(Chain::class)
        ->args([
            tagged_iterator('phpro.api_problem.exception_transformer'),
        ]);

    $services->set(ApiProblemExceptionTransformer::class)
        ->class(ApiProblemExceptionTransformer::class)
        ->tag('phpro.api_problem.exception_transformer');

    $services->set(HttpExceptionTransformer::class)
        ->class(HttpExceptionTransformer::class)
        ->tag('phpro.api_problem.exception_transformer');

    $services->set(SecurityExceptionTransformer::class)
        ->class(SecurityExceptionTransformer::class)
        ->tag('phpro.api_problem.exception_transformer');
};