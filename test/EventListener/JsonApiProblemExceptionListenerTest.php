<?php

declare(strict_types=1);

namespace PhproTest\ApiProblemBundle\EventListener;

use Exception;

use function json_encode;

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
     * @var ExceptionTransformerInterface|ObjectProphecy
     */
    private $exceptionTransformer;

    protected function setUp(): void
    {
        $this->exceptionTransformer = $this->prophesize(ExceptionTransformerInterface::class);
        $this->exceptionTransformer->accepts(Argument::any())->willReturn(false);
    }

    /** @test */
    public function it_does_nothing_on_non_json_requests(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $request->headers->set('Content-Type', 'text/html');

        $event = $this->buildEvent($request);
        $listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    /** @test */
    public function it_runs_on_json_route_formats(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);

        $request = new Request();
        $request->headers->set('Accept', 'application/json');
        $request->headers->remove('Content-Type');
        $exception = new Exception('error');
        $event = $this->buildEvent($request, $exception);

        $listener->onKernelException($event);

        $this->assertApiProblemWithResponseBody($event, 500, $this->parseDataForException($exception));
    }

    /** @test */
    public function it_runs_on_json_content_types(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $request->headers->set('Content-Type', 'application/json');
        $exception = new Exception('error');
        $event = $this->buildEvent($request, $exception);

        $listener->onKernelException($event);
        $this->assertApiProblemWithResponseBody($event, 500, $this->parseDataForException($exception));
    }

    /** @test */
    public function it_runs_on_json_accept_header(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);

        $request = new Request();
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'text/html');
        $exception = new Exception('error');
        $event = $this->buildEvent($request, $exception);

        $listener->onKernelException($event);
        $this->assertApiProblemWithResponseBody($event, 500, $this->parseDataForException($exception));
    }

    /** @test */
    public function it_parses_an_api_problem_response(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);

        $request = new Request();
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');
        $exception = new Exception('error');
        $event = $this->buildEvent($request, $exception);

        $listener->onKernelException($event);
        $this->assertApiProblemWithResponseBody($event, 500, $this->parseDataForException($exception));
    }

    /** @test */
    public function it_uses_an_exception_transformer(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);

        $request = new Request();
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'text/html');
        $event = $this->buildEvent($request);

        $apiProblem = $this->prophesize(ApiProblemInterface::class);
        $apiProblem->toArray()->willReturn([]);

        $this->exceptionTransformer->accepts(Argument::type(Exception::class))->willReturn(true);
        $this->exceptionTransformer->transform(Argument::type(Exception::class))->willReturn($apiProblem->reveal());

        $listener->onKernelException($event);
        $this->assertApiProblemWithResponseBody($event, 400, []);
    }

    /** @test */
    public function it_returns_the_status_code_from_the_api_problem(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), false);

        $request = new Request();
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'text/html');
        $event = $this->buildEvent($request);

        $apiProblem = $this->prophesize(ApiProblemInterface::class);
        $apiProblem->toArray()->willReturn(['status' => 123]);

        $this->exceptionTransformer->accepts(Argument::type(Exception::class))->willReturn(true);
        $this->exceptionTransformer->transform(Argument::type(Exception::class))->willReturn($apiProblem->reveal());

        $listener->onKernelException($event);
        $this->assertApiProblemWithResponseBody($event, 123, ['status' => 123]);
    }

    /** @test */
    public function it_parses_a_debuggable_api_problem_response(): void
    {
        $listener = new JsonApiProblemExceptionListener($this->exceptionTransformer->reveal(), true);
        $apiProblem = $this->prophesize(DebuggableApiProblemInterface::class);

        $data = ['status' => 500, 'detail' => 'detail', 'debug' => true];
        $apiProblem->toDebuggableArray()->willReturn($data);
        $apiProblem->toArray()->willReturn($data);

        $exception = new Exception('error');
        $this->exceptionTransformer->accepts($exception)->willReturn(true);
        $this->exceptionTransformer->transform($exception)->willReturn($apiProblem->reveal());

        $request = new Request();
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'text/html');
        $event = $this->buildEvent($request, $exception);

        $listener->onKernelException($event);
        $this->assertApiProblemWithResponseBody($event, 500, $data);
    }

    private function assertApiProblemWithResponseBody(ExceptionEvent $event, int $expectedResponseCode, array $expectedData): void
    {
        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($expectedResponseCode, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedData),
            $event->getResponse()->getContent()
        );
    }

    private function parseDataForException(Exception $exception): array
    {
        return [
            'status' => 500,
            'type' => HttpApiProblem::TYPE_HTTP_RFC,
            'title' => HttpApiProblem::getTitleForStatusCode(500),
            'detail' => $exception->getMessage(),
        ];
    }

    private function buildEvent(Request $request, ?Exception $exception = null): ExceptionEvent
    {
        $exception ??= new Exception('error');

        $httpKernel = $this->prophesize(HttpKernelInterface::class);

        return new ExceptionEvent(
            $httpKernel->reveal(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }
}
