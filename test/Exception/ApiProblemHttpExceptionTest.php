<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle\Exception;

use Phpro\ApiProblem\Exception\ApiProblemException;
use Phpro\ApiProblem\Http\HttpApiProblem;
use Phpro\ApiProblemBundle\Exception\ApiProblemHttpException;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ApiProblemHttpExceptionTest extends TestCase
{
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
    public function it_is_an_instance_of_ApiProblemException(): void
    {
        $exception = new ApiProblemHttpException($this->apiProblem->reveal());

        $this->assertInstanceOf(ApiProblemException::class, $exception);
    }

    /** @test */
    public function it_is_an_instance_of_HttpException(): void
    {
        $exception = new ApiProblemHttpException($this->apiProblem->reveal());

        $this->assertInstanceOf(HttpExceptionInterface::class, $exception);
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
