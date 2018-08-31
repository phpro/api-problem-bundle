<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle\Transformer;

use Phpro\ApiProblem\Http\ForbiddenProblem;
use Phpro\ApiProblem\Http\HttpApiProblem;
use Phpro\ApiProblem\Http\UnauthorizedProblem;
use Phpro\ApiProblemBundle\Transformer\ExceptionTransformerInterface;
use Phpro\ApiProblemBundle\Transformer\SecurityExceptionTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\RuntimeException;

/**
 * @covers \Phpro\ApiProblemBundle\Transformer\SecurityExceptionTransformer
 */
class SecurityExceptionTransformerTest extends TestCase
{
    /** @test */
    public function it_is_an_exception_transformer(): void
    {
        $transformer = new SecurityExceptionTransformer();
        $this->assertInstanceOf(ExceptionTransformerInterface::class, $transformer);
    }

    /** @test */
    public function it_accepts_api_problem_exceptions(): void
    {
        $transformer = new SecurityExceptionTransformer();

        $this->assertTrue($transformer->accepts(new RuntimeException()));
        $this->assertFalse($transformer->accepts(new \Exception()));
    }

    /** @test */
    public function it_transforms_authentication_exception_to_api_problem(): void
    {
        $transformer = new SecurityExceptionTransformer();
        $exception = new AuthenticationException($detail = 'not authenticated');
        $apiProblem = $transformer->transform($exception);

        $this->assertInstanceOf(UnauthorizedProblem::class, $apiProblem);
        $this->assertEquals([
            'status' => 401,
            'title' => HttpApiProblem::getTitleForStatusCode(401),
            'detail' => $detail,
            'type' => HttpApiProblem::TYPE_HTTP_RFC,
        ], $apiProblem->toArray());
    }

    /** @test */
    public function it_transforms_other_security_exceptions_to_api_problem(): void
    {
        $transformer = new SecurityExceptionTransformer();
        $exception = new AccessDeniedException($detail = 'Invalid roles');
        $apiProblem = $transformer->transform($exception);

        $this->assertInstanceOf(ForbiddenProblem::class, $apiProblem);
        $this->assertEquals([
            'status' => 403,
            'title' => HttpApiProblem::getTitleForStatusCode(403),
            'detail' => $detail,
            'type' => HttpApiProblem::TYPE_HTTP_RFC,
        ], $apiProblem->toArray());
    }
}
