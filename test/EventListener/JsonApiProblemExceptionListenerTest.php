<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle\EventListener;

use Phpro\ApiProblem\ApiProblemInterface;
use Phpro\ApiProblem\DebuggableApiProblemInterface;
use Phpro\ApiProblem\Http\HttpApiProblem;
use Phpro\ApiProblemBundle\EventListener\JsonApiProblemExceptionListener;
use Phpro\ApiProblemBundle\Transformer\ExceptionTransformerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/** @covers \Phpro\ApiProblemBundle\EventListener\JsonApiProblemExceptionListener */
class JsonApiProblemExceptionListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy|Request
     */
    private $request;

    /**
     * @var ExceptionEvent
     */
    private $event;

    /**
     * @var \Throwable
     */
    private $exception;

    /**
     * @var ExceptionTransformerInterface|ObjectProphecy
     */
    private $exceptionTransformer;

    protected function setUp(): void
    {
        $this->request = $this->prophesize(Request::class);
        $httpKernel = $this->prophesize(HttpKernelInterface::class);
        $this->exception = new \Exception('error');
        $this->event = new ExceptionEvent(
            $httpKernel->reveal(),
            $this->request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $this->exception
        );
        $this->exceptionTransformer = $this->prophesize(ExceptionTransformerInterface::class);
        $this->exceptionTransformer->accepts(Argument::any())->willReturn(false);
    }

    /** @test */
    public function it_does_nothing_on_non_json_requests(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);
        $this->request->getPreferredFormat()->willReturn('html');
        $this->request->getContentType()->willReturn('text/html');
        $listener->onKernelException($this->event);

        $this->assertNull($this->event->getResponse());
    }

    /** @test */
    public function it_runs_on_json_route_formats(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);
        $this->request->getPreferredFormat()->willReturn('json');
        $this->request->getContentType()->willReturn(null);
        $listener->onKernelException($this->event);

        $this->assertApiProblemWithResponseBody(500, $this->parseDataForException());
    }

    /** @test */
    public function it_runs_on_json_content_types(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);
        $this->request->getPreferredFormat()->willReturn('html');
        $this->request->getContentType()->willReturn('application/json');

        $listener->onKernelException($this->event);
        $this->assertApiProblemWithResponseBody(500, $this->parseDataForException());
    }

    /** @test */
    public function it_runs_on_json_accept_header(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);
        $this->request->getPreferredFormat()->willReturn('json');
        $this->request->getContentType()->willReturn('html');

        $listener->onKernelException($this->event);
        $this->assertApiProblemWithResponseBody(500, $this->parseDataForException());
    }

    /** @test */
    public function it_parses_an_api_problem_response(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);
        $this->request->getPreferredFormat()->willReturn('json');
        $this->request->getContentType()->willReturn('application/json');

        $listener->onKernelException($this->event);
        $this->assertApiProblemWithResponseBody(500, $this->parseDataForException());
    }

    /** @test */
    public function it_uses_an_exception_transformer(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);
        $this->request->getPreferredFormat()->willReturn('json');
        $this->request->getContentType()->willReturn('application/json');

        $apiProblem = $this->prophesize(ApiProblemInterface::class);
        $apiProblem->toArray()->willReturn([]);

        $this->exceptionTransformer->accepts(Argument::type(\Exception::class))->willReturn(true);
        $this->exceptionTransformer->transform(Argument::type(\Exception::class))->willReturn($apiProblem->reveal());

        $listener->onKernelException($this->event);
        $this->assertApiProblemWithResponseBody(400, []);
    }

    /** @test */
    public function it_returns_the_status_code_from_the_api_problem(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);
        $this->request->getPreferredFormat()->willReturn('json');
        $this->request->getContentType()->willReturn('application/json');

        $apiProblem = $this->prophesize(ApiProblemInterface::class);
        $apiProblem->toArray()->willReturn(['status' => 123]);

        $this->exceptionTransformer->accepts(Argument::type(\Exception::class))->willReturn(true);
        $this->exceptionTransformer->transform(Argument::type(\Exception::class))->willReturn($apiProblem->reveal());

        $listener->onKernelException($this->event);
        $this->assertApiProblemWithResponseBody(123, ['status' => 123]);
    }

    /** @test */
    public function it_parses_a_debuggable_api_problem_response(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), true);
        $apiProblem = $this->prophesize(DebuggableApiProblemInterface::class);

        $data = ['status' => 500, 'detail' => 'detail', 'debug' => true];
        $apiProblem->toDebuggableArray()->willReturn($data);
        $apiProblem->toArray()->willReturn($data);

        $this->exceptionTransformer->accepts($this->exception)->willReturn(true);
        $this->exceptionTransformer->transform($this->exception)->willReturn($apiProblem->reveal());

        $this->request->getPreferredFormat()->willReturn('json');
        $this->request->getContentType()->willReturn('application/json');

        $listener->onKernelException($this->event);
        $this->assertApiProblemWithResponseBody(500, $data);
    }

    private function assertApiProblemWithResponseBody(int $expectedResponseCode, array $expectedData): void
    {
        $response = $this->event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($expectedResponseCode, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertJsonStringEqualsJsonString(
            \json_encode($expectedData),
            $this->event->getResponse()->getContent()
        );
    }

    private function parseDataForException(): array
    {
        return [
            'status' => 500,
            'type' => HttpApiProblem::TYPE_HTTP_RFC,
            'title' => HttpApiProblem::getTitleForStatusCode(500),
            'detail' => $this->exception->getMessage(),
        ];
    }
}
