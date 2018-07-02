<?php

declare(strict_types=1);

namespace spec\Phpro\ApiProblemBundle\EventListener;

use Phpro\ApiProblem\DebuggableApiProblemInterface;
use Phpro\ApiProblem\Exception\ApiProblemException;
use Phpro\ApiProblem\Http\HttpApiProblem;
use Phpro\ApiProblemBundle\EventListener\JsonApiProblemExceptionListener;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class JsonApiProblemExceptionListenerSpec extends ObjectBehavior
{
    public function let(GetResponseForExceptionEvent $event, Request $request): void
    {
        $this->beConstructedWith(false);
        $event->getRequest()->willReturn($request);
        $event->getException()->willReturn(new \Exception('error'));
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(JsonApiProblemExceptionListener::class);
    }

    public function it_does_nothing_on_non_json_requests(GetResponseForExceptionEvent $event, Request $request): void
    {
        $request->getRequestFormat()->willReturn('html');
        $request->getContentType()->willReturn('text/html');

        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->onKernelException($event);
    }

    public function it_runs_on_json_route_formats(GetResponseForExceptionEvent $event, Request $request): void
    {
        $request->getRequestFormat()->willReturn('json');
        $request->getContentType()->willReturn(null);

        $event->setResponse(Argument::type(JsonResponse::class))->shouldBeCalled();

        $this->onKernelException($event);
    }

    public function it_runs_on_json_content_types(GetResponseForExceptionEvent $event, Request $request): void
    {
        $request->getRequestFormat()->willReturn('html');
        $request->getContentType()->willReturn('application/json');

        $event->setResponse(Argument::type(JsonResponse::class))->shouldBeCalled();

        $this->onKernelException($event);
    }

    public function it_parses_an_api_problem_response(GetResponseForExceptionEvent $event, Request $request): void
    {
        $request->getRequestFormat()->willReturn('json');
        $request->getContentType()->willReturn('application/json');

        $event->setResponse(Argument::that(function (JsonResponse $response) {
            return 500 === $response->getStatusCode()
                && 'application/problem+json' === $response->headers->get('Content-Type')
                && $response->getContent() === json_encode([
                    'status' => 500,
                    'type' => HttpApiProblem::TYPE_HTTP_RFC,
                    'title' => HttpApiProblem::getTitleForStatusCode(500),
                    'detail' => 'error',
                ]);
        }))->shouldBeCalled();

        $this->onKernelException($event);
    }

    public function it_parses_a_debuggable_api_problem_response(
        GetResponseForExceptionEvent $event,
        Request $request,
        DebuggableApiProblemInterface $apiProblem
    ): void {
        $this->beConstructedWith(true);
        $data = ['status' => 500, 'detail' => 'detail', 'debug' => true];
        $apiProblem->toDebuggableArray()->willReturn($data);
        $apiProblem->toArray()->willReturn($data);
        $event->getException()->willReturn(new ApiProblemException($apiProblem->getWrappedObject()));

        $request->getRequestFormat()->willReturn('json');
        $request->getContentType()->willReturn('application/json');

        $event->setResponse(Argument::that(function (JsonResponse $response) use ($data) {
            return 500 === $response->getStatusCode()
                && 'application/problem+json' === $response->headers->get('Content-Type')
                && $response->getContent() === json_encode($data);
        }))->shouldBeCalled();

        $this->onKernelException($event);
    }
}
