<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle\Transformer;

use Exception;
use Phpro\ApiProblem\Http\ExceptionApiProblem;
use Phpro\ApiProblem\Http\HttpApiProblem;
use Phpro\ApiProblemBundle\Transformer\ExceptionTransformerInterface;
use Phpro\ApiProblemBundle\Transformer\HttpExceptionTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[CoversClass(HttpExceptionTransformer::class)]
class HttpExceptionTransformerTest extends TestCase
{
    #[Test]
    public function it_is_an_exception_transformer(): void
    {
        $transformer = new HttpExceptionTransformer();
        $this->assertInstanceOf(ExceptionTransformerInterface::class, $transformer);
    }

    #[Test]
    public function it_accepts_api_problem_exceptions(): void
    {
        $transformer = new HttpExceptionTransformer();

        $this->assertTrue($transformer->accepts(new HttpException(400, 'Bad Request')));
        $this->assertFalse($transformer->accepts(new Exception()));
    }

    #[Test]
    public function it_transforms_exception_to_api_problem(): void
    {
        $transformer = new HttpExceptionTransformer();
        $exception = new HttpException($statusCode = 400, $detail = 'Bad Request');
        $apiProblem = $transformer->transform($exception);

        $this->assertInstanceOf(ExceptionApiProblem::class, $apiProblem);
        $this->assertEquals([
            'status' => $statusCode,
            'title' => HttpApiProblem::getTitleForStatusCode($statusCode),
            'detail' => $detail,
            'type' => HttpApiProblem::TYPE_HTTP_RFC,
        ], $apiProblem->toArray());
    }
}
