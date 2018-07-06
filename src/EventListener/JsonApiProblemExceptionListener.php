<?php

declare(strict_types=1);

namespace Phpro\ApiProblemBundle\EventListener;

use Phpro\ApiProblem\ApiProblemInterface;
use Phpro\ApiProblem\DebuggableApiProblemInterface;
use Phpro\ApiProblem\Exception\ApiProblemException;
use Phpro\ApiProblem\Http\ExceptionApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class JsonApiProblemExceptionListener
{
    /**
     * @var bool
     */
    private $debug;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (
            false === mb_strpos($request->getRequestFormat(), 'json') &&
            false === mb_strpos((string) $request->getContentType(), 'json')
        ) {
            return;
        }

        $apiProblem = $this->convertExceptionToProblem($event->getException());
        $event->setResponse($this->generateResponse($apiProblem));
    }

    private function convertExceptionToProblem(\Throwable $exception): ApiProblemInterface
    {
        if ($exception instanceof ApiProblemException) {
            return $exception->getApiProblem();
        }

        return new ExceptionApiProblem($exception);
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
