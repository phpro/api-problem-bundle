<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle\EventListener;

use Phpro\ApiProblem\DebuggableApiProblemInterface;
use Phpro\ApiProblem\Exception\ApiProblemException;
use Phpro\ApiProblem\Http\HttpApiProblem;
use Phpro\ApiProblemBundle\EventListener\JsonApiProblemExceptionListener;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/** @covers \Phpro\ApiProblemBundle\EventListener\JsonApiProblemExceptionListener */
class JsonApiProblemExceptionListenerTest extends TestCase
{
    /**
     * @var ObjectProphecy|Request
     */
    private $request;

    /**
     * @var GetResponseForExceptionEvent|ObjectProphecy
     */
    private $event;

    protected function setUp(): void
    {
        $this->request = $this->prophesize(Request::class);
        $this->event = $this->prophesize(GetResponseForExceptionEvent::class);
        $this->event->getRequest()->willReturn($this->request);
        $this->event->getException()->willReturn(new \Exception('error'));
    }

    /** @test */
    public function it_does_nothing_on_non_json_requests(): void
    {
        $listener = new JsonApiProblemExceptionListener(false);
        $this->request->getRequestFormat()->willReturn('html');
        $this->request->getContentType()->willReturn('text/html');

        $this->event->setResponse(Argument::any())->shouldNotBeCalled();

        $listener->onKernelException($this->event->reveal());
    }

    /** @test */
    public function it_runs_on_json_route_formats(): void
    {
        $listener = new JsonApiProblemExceptionListener(false);
        $this->request->getRequestFormat()->willReturn('json');
        $this->request->getContentType()->willReturn(null);

        $this->event->setResponse(Argument::type(JsonResponse::class))->shouldBeCalled();

        $listener->onKernelException($this->event->reveal());
    }

    /** @test */
    public function it_runs_on_json_content_types(): void
    {
        $listener = new JsonApiProblemExceptionListener(false);
        $this->request->getRequestFormat()->willReturn('html');
        $this->request->getContentType()->willReturn('application/json');

        $this->event->setResponse(Argument::type(JsonResponse::class))->shouldBeCalled();

        $listener->onKernelException($this->event->reveal());
    }

    /** @test */
    public function it_parses_an_api_problem_response(): void
    {
        $listener = new JsonApiProblemExceptionListener(false);
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
    public function it_parses_a_debuggable_api_problem_response(): void
    {
        $listener = new JsonApiProblemExceptionListener(true);
        $apiProblem = $this->prophesize(DebuggableApiProblemInterface::class);

        $data = ['status' => 500, 'detail' => 'detail', 'debug' => true];
        $apiProblem->toDebuggableArray()->willReturn($data);
        $apiProblem->toArray()->willReturn($data);
        $this->event->getException()->willReturn(new ApiProblemException($apiProblem->reveal()));

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
