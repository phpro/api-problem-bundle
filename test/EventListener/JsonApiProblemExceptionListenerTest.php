<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle\EventListener;

use DG\BypassFinals;
use Phpro\ApiProblem\ApiProblemInterface;
use Phpro\ApiProblem\DebuggableApiProblemInterface;
use Phpro\ApiProblem\Http\HttpApiProblem;
use Phpro\ApiProblemBundle\EventListener\JsonApiProblemExceptionListener;
use Phpro\ApiProblemBundle\Transformer\ExceptionTransformerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/** @covers \Phpro\ApiProblemBundle\EventListener\JsonApiProblemExceptionListener */
class JsonApiProblemExceptionListenerTest extends TestCase
{
    /**
     * @var ObjectProphecy|Request
     */
    private $request;

    /**
     * @var ExceptionEvent|ObjectProphecy
     */
    private $event;

    /**
     * @var ExceptionTransformerInterface|ObjectProphecy
     */
    private $exceptionTransformer;

    protected function setUp(): void
    {
        BypassFinals::enable();
        $this->request = $this->prophesize(Request::class);
        $this->event = $this->prophesize(ExceptionEvent::class);
        $this->event->getRequest()->willReturn($this->request);
        $this->event->getThrowable()->willReturn(new \Exception('error'));
        $this->exceptionTransformer = $this->prophesize(ExceptionTransformerInterface::class);
        $this->exceptionTransformer->accepts(Argument::any())->willReturn(false);
    }

    /** @test */
    public function it_does_nothing_on_non_json_requests(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);
        $this->request->getRequestFormat()->willReturn('html');
        $this->request->getContentType()->willReturn('text/html');

        $this->event->setResponse(Argument::any())->shouldNotBeCalled();

        $listener->onKernelException($this->event->reveal());
    }

    /** @test */
    public function it_runs_on_json_route_formats(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);
        $this->request->getRequestFormat()->willReturn('json');
        $this->request->getContentType()->willReturn(null);

        $this->event->setResponse(Argument::type(JsonResponse::class))->shouldBeCalled();

        $listener->onKernelException($this->event->reveal());
    }

    /** @test */
    public function it_runs_on_json_content_types(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);
        $this->request->getRequestFormat()->willReturn('html');
        $this->request->getContentType()->willReturn('application/json');

        $this->event->setResponse(Argument::type(JsonResponse::class))->shouldBeCalled();

        $listener->onKernelException($this->event->reveal());
    }

    /** @test */
    public function it_parses_an_api_problem_response(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);
        $this->request->getRequestFormat()->willReturn('json');
        $this->request->getContentType()->willReturn('application/json');

        $this->event->setResponse(Argument::that(function (JsonResponse $response) {
            return 500 === $response->getStatusCode()
                && 'application/problem+json' === $response->headers->get('Content-Type')
                && $response->getContent() === json_encode([
                    'status' => 500,
                    'type' => HttpApiProblem::TYPE_HTTP_RFC,
                    'title' => HttpApiProblem::getTitleForStatusCode(500),
                    'detail' => 'error',
                ]);
        }))->shouldBeCalled();

        $listener->onKernelException($this->event->reveal());
    }

    /** @test */
    public function it_uses_an_exception_transformer(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);
        $this->request->getRequestFormat()->willReturn('json');
        $this->request->getContentType()->willReturn('application/json');

        $apiProblem = $this->prophesize(ApiProblemInterface::class);
        $apiProblem->toArray()->willReturn([]);

        $this->exceptionTransformer->accepts(Argument::type(\Exception::class))->willReturn(true);
        $this->exceptionTransformer->transform(Argument::type(\Exception::class))->willReturn($apiProblem->reveal());

        $this->event->setResponse(Argument::that(function (JsonResponse $response) {
            return Response::HTTP_BAD_REQUEST === $response->getStatusCode()
                && 'application/problem+json' === $response->headers->get('Content-Type')
                && $response->getContent() === json_encode([]);
        }))->shouldBeCalled();

        $listener->onKernelException($this->event->reveal());
    }

    /** @test */
    public function it_parses_a_debuggable_api_problem_response(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), true);
        $apiProblem = $this->prophesize(DebuggableApiProblemInterface::class);

        $data = ['status' => 500, 'detail' => 'detail', 'debug' => true];
        $apiProblem->toDebuggableArray()->willReturn($data);
        $apiProblem->toArray()->willReturn($data);
        $exception = new \RuntimeException();

        $this->event->getThrowable()->willReturn($exception);
        $this->exceptionTransformer->accepts($exception)->willReturn(true);
        $this->exceptionTransformer->transform($exception)->willReturn($apiProblem->reveal());

        $this->request->getRequestFormat()->willReturn('json');
        $this->request->getContentType()->willReturn('application/json');

        $this->event->setResponse(Argument::that(function (JsonResponse $response) use ($data) {
            return 500 === $response->getStatusCode()
                && 'application/problem+json' === $response->headers->get('Content-Type')
                && $response->getContent() === json_encode($data);
        }))->shouldBeCalled();

        $listener->onKernelException($this->event->reveal());
    }
}
