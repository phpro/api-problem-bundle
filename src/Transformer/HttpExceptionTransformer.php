<?php

declare(strict_types=1);

namespace Phpro\ApiProblemBundle\Transformer;

use Phpro\ApiProblem\ApiProblemInterface;
use Phpro\ApiProblem\Http\ExceptionApiProblem;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HttpExceptionTransformer implements ExceptionTransformerInterface
{
    /**
     * @param HttpException $exception
     */
    public function transform(\Throwable $exception): ApiProblemInterface
    {
        return new ExceptionApiProblem(
            new HttpException(
                $exception->getStatusCode(),
                $exception->getMessage(),
                $exception,
                $exception->getHeaders(),
                $exception->getStatusCode()
            )
        );
    }

    public function accepts(\Throwable $exception): bool
    {
        return $exception instanceof HttpException;
    }
}
