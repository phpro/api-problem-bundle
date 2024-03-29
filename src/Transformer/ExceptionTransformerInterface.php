<?php

declare(strict_types=1);

namespace Phpro\ApiProblemBundle\Transformer;

use Phpro\ApiProblem\ApiProblemInterface;
use Throwable;

interface ExceptionTransformerInterface
{
    public function transform(Throwable $exception): ApiProblemInterface;

    public function accepts(Throwable $exception): bool;
}
