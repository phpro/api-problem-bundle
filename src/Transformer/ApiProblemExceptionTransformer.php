<?php

declare(strict_types=1);

namespace Phpro\ApiProblemBundle\Transformer;

use Phpro\ApiProblem\ApiProblemInterface;
use Phpro\ApiProblem\Exception\ApiProblemException;
use Phpro\ApiProblemBundle\Exception\ApiProblemHttpException;
use Throwable;

class ApiProblemExceptionTransformer implements ExceptionTransformerInterface
{
    /**
     * @param ApiProblemException|ApiProblemHttpException $exception
     */
    public function transform(Throwable $exception): ApiProblemInterface
    {
        return $exception->getApiProblem();
    }

    public function accepts(Throwable $exception): bool
    {
        return $exception instanceof ApiProblemException || $exception instanceof ApiProblemHttpException;
    }
}
