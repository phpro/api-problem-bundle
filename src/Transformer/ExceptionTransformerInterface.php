<?php

declare(strict_types=1);

namespace Phpro\ApiProblemBundle\Transformer;

use Phpro\ApiProblem\ApiProblemInterface;
use Throwable;

/**
 * @template T of \Throwable
 */
interface ExceptionTransformerInterface
{
    /**
     * @param T $exception
     */
    public function transform(Throwable $exception): ApiProblemInterface;

    /**
     * @psalm-assert-if-true T $exception
     */
    public function accepts(Throwable $exception): bool;
}
