<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle\Exception;

use Phpro\ApiProblem\Http\HttpApiProblem;
use Phpro\ApiProblemBundle\Exception\ApiProblemHttpException;
use Phpro\ApiProblemBundle\Transformer\ApiProblemExceptionTransformer;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiProblemHttpExceptionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var HttpApiProblem|ObjectProphecy
     */
    private $apiProblem;

    protected function setUp(): void/* The :void return type declaration that should be here would cause a BC issue */
    {
        $this->apiProblem = $this->prophesize(HttpApiProblem::class);
        $this->apiProblem->toArray()->willReturn([]);
    }

    /** @test */
    public function it_is_accepted_by_the_ApiProblemExceptionTransformer(): void
    {
        $transformer = new ApiProblemExceptionTransformer();

        $this->assertTrue($transformer->accepts(new ApiProblemHttpException($this->apiProblem->reveal())));
    }

    /** @test */
    public function it_is_an_instance_of_HttpException(): void
    {
        $exception = new ApiProblemHttpException($this->apiProblem->reveal());

        $this->assertInstanceOf(HttpException::class, $exception);
    }

    /** @test */
    public function it_contains_an_api_problem(): void
    {
        $apiProblem = $this->apiProblem->reveal();

        $exception = new ApiProblemHttpException($apiProblem);
        $this->assertEquals($apiProblem, $exception->getApiProblem());
    }

    /** @test */
    public function it_returns_the_correct_http_headers(): void
    {
        $exception = new ApiProblemHttpException($this->apiProblem->reveal());

        $this->assertEquals(['Content-Type' => 'application/problem+json'], $exception->getHeaders());
    }

    /** @test */
    public function it_returns_the_correct_default_http_statuscode(): void
    {
        $exception = new ApiProblemHttpException($this->apiProblem->reveal());

        $this->assertEquals(400, $exception->getStatusCode());
    }

    /** @test */
    public function it_returns_the_correct_specified_http_statuscode(): void
    {
        $this->apiProblem->toArray()->willReturn(['status' => 401]);
        $exception = new ApiProblemHttpException($this->apiProblem->reveal());

        $this->assertEquals(401, $exception->getStatusCode());
    }
}
