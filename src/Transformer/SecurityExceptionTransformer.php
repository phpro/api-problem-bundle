<?php

declare(strict_types=1);

namespace Phpro\ApiProblemBundle\Transformer;

use Phpro\ApiProblem\ApiProblemInterface;
use Phpro\ApiProblem\Http\ForbiddenProblem;
use Phpro\ApiProblem\Http\UnauthorizedProblem;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\ExceptionInterface as SecurityException;
use Throwable;

/**
 * @template-implements ExceptionTransformerInterface<SecurityException>
 */
class SecurityExceptionTransformer implements ExceptionTransformerInterface
{
    public function transform(Throwable $exception): ApiProblemInterface
    {
        if ($exception instanceof AuthenticationException) {
            return new UnauthorizedProblem($exception->getMessage());
        }

        return new ForbiddenProblem($exception->getMessage());
    }

    public function accepts(Throwable $exception): bool
    {
        return $exception instanceof SecurityException;
    }
}
