<?php

declare(strict_types=1);

namespace Phpro\ApiProblemBundle\Transformer;

use Phpro\ApiProblem\ApiProblemInterface;
use Phpro\ApiProblem\Http\ExceptionApiProblem;

class Chain implements ExceptionTransformerInterface
{
    /**
     * @var ExceptionTransformerInterface[]
     */
    private $transformers;

    public function __construct(ExceptionTransformerInterface ...$transformers)
    {
        $this->transformers = $transformers;
    }

    public function transform(\Throwable $exception): ApiProblemInterface
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->accepts($exception)) {
                return $transformer->transform($exception);
            }
        }

        return new ExceptionApiProblem($exception);
    }

    public function accepts(\Throwable $exception): bool
    {
        return true;
    }
}
