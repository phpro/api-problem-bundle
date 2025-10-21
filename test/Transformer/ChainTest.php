<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle\Transformer;

use Exception;
use Phpro\ApiProblem\ApiProblemInterface;
use Phpro\ApiProblem\Http\ExceptionApiProblem;
use Phpro\ApiProblemBundle\Transformer\Chain;
use Phpro\ApiProblemBundle\Transformer\ExceptionTransformerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(Chain::class)]
class ChainTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_is_an_exception_transformer(): void
    {
        $transformer = new Chain([]);
        $this->assertInstanceOf(ExceptionTransformerInterface::class, $transformer);
    }

    #[Test]
    public function it_accepts_any_exception(): void
    {
        $transformer = new Chain([]);
        $this->assertTrue($transformer->accepts(new Exception()));
    }

    #[Test]
    public function it_transforms_with_first_acceptable_transformer(): void
    {
        $transformer = new Chain([
            $this->mockTransformer(false),
            $this->mockTransformer(true, $apiProblem1 = $this->prophesize(ApiProblemInterface::class)->reveal()),
            $this->mockTransformer(true, $apiProblem2 = $this->prophesize(ApiProblemInterface::class)->reveal()),
        ]);

        $this->assertEquals($apiProblem1, $transformer->transform(new Exception()));
    }

    #[Test]
    public function it_transforms_to_basic_exception_problem_when_no_transformer_matches(): void
    {
        $transformer = new Chain([$this->mockTransformer(false)]);

        $this->assertInstanceOf(ExceptionApiProblem::class, $transformer->transform(new Exception()));
    }

    private function mockTransformer(bool $accepts, ?ApiProblemInterface $apiProblem = null): ExceptionTransformerInterface
    {
        /** @var ExceptionTransformerInterface|ObjectProphecy $transformer */
        $transformer = $this->prophesize(ExceptionTransformerInterface::class);
        $transformer->accepts(Argument::any())->willReturn($accepts);

        if ($apiProblem) {
            $transformer->transform(Argument::any())->willReturn($apiProblem);
        }
        if (!$accepts) {
            $transformer->transform(Argument::any())->shouldNotBeCalled();
        }

        return $transformer->reveal();
    }
}
