<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle\Transformer;

use Exception;
use Phpro\ApiProblem\ApiProblemInterface;
use Phpro\ApiProblem\Exception\ApiProblemException;
use Phpro\ApiProblemBundle\Transformer\ApiProblemExceptionTransformer;
use Phpro\ApiProblemBundle\Transformer\ExceptionTransformerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @covers \Phpro\ApiProblemBundle\Transformer\ApiProblemExceptionTransformer
 */
class ApiProblemExceptionTransformerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ApiProblemInterface|ObjectProphecy
     */
    private $apiProblem;

    protected function setUp(): void/* The :void return type declaration that should be here would cause a BC issue */
    {
        $this->apiProblem = $this->prophesize(ApiProblemInterface::class);
        $this->apiProblem->toArray()->willReturn([]);
    }

    /** @test */
    public function it_is_an_exception_transformer(): void
    {
        $transformer = new ApiProblemExceptionTransformer();
        $this->assertInstanceOf(ExceptionTransformerInterface::class, $transformer);
    }

    /** @test */
    public function it_accepts_api_problem_exceptions(): void
    {
        $transformer = new ApiProblemExceptionTransformer();

        $this->assertTrue($transformer->accepts(new ApiProblemException($this->apiProblem->reveal())));
        $this->assertFalse($transformer->accepts(new Exception()));
    }

    /** @test */
    public function it_transforms_exception_to_api_problem(): void
    {
        $transformer = new ApiProblemExceptionTransformer();
        $apiProblem = $this->apiProblem->reveal();

        $this->assertSame($apiProblem, $transformer->transform(new ApiProblemException($apiProblem)));
    }
}
