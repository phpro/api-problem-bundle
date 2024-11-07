<?php

declare(strict_types=1);

namespace Phpro\ApiProblemBundle\Transformer;

use Phpro\ApiProblem\ApiProblemInterface;
use Phpro\ApiProblem\Http\ExceptionApiProblem;
use Throwable;

/**
 * @template-implements ExceptionTransformerInterface<Throwable>
 */
class Chain implements ExceptionTransformerInterface
{
    /**
     * @var ExceptionTransformerInterface[]
     */
    private $transformers = [];

    public function __construct(iterable $transformers)
    {
        foreach ($transformers as $transformer) {
            $this->addTransformer($transformer);
        }
    }

    public function transform(Throwable $exception): ApiProblemInterface
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->accepts($exception)) {
                return $transformer->transform($exception);
            }
        }

        return new ExceptionApiProblem($exception);
    }

    public function accepts(Throwable $exception): bool
    {
        return true;
    }

    private function addTransformer(ExceptionTransformerInterface $transformer): void
    {
        $this->transformers[] = $transformer;
    }
}
