<?php

declare(strict_types=1);

namespace Phpro\ApiProblemBundle\EventListener;

use Phpro\ApiProblem\ApiProblemInterface;
use Phpro\ApiProblem\DebuggableApiProblemInterface;
use Phpro\ApiProblem\Http\ExceptionApiProblem;
use Phpro\ApiProblemBundle\Transformer\ExceptionTransformerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class JsonApiProblemExceptionListener
{
    /**
     * @var ExceptionTransformerInterface
     */
    private $exceptionTransformer;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(ExceptionTransformerInterface $exceptionTransformer, bool $debug)
    {
        $this->exceptionTransformer = $exceptionTransformer;
        $this->debug = $debug;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (
            false === mb_strpos($request->getRequestFormat(), 'json') &&
            false === mb_strpos((string) $request->getContentType(), 'json')
        ) {
            return;
        }

        $apiProblem = $this->convertExceptionToProblem($event->getThrowable());
        $event->setResponse($this->generateResponse($apiProblem));
    }

    private function convertExceptionToProblem(\Throwable $exception): ApiProblemInterface
    {
        if (!$this->exceptionTransformer->accepts($exception)) {
            return new ExceptionApiProblem($exception);
        }

        return $this->exceptionTransformer->transform($exception);
    }

    private function generateResponse(ApiProblemInterface $apiProblem): JsonResponse
    {
        $data = ($this->debug && $apiProblem instanceof DebuggableApiProblemInterface)
            ? $apiProblem->toDebuggableArray()
            : $apiProblem->toArray();

        $statusCode = (int) ($data['status'] ?? Response::HTTP_BAD_REQUEST);

        return new JsonResponse(
            $data,
            $statusCode,
            ['Content-Type' => 'application/problem+json']
        );
    }
}
