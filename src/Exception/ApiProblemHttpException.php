<?php

declare(strict_types=1);

namespace Phpro\ApiProblemBundle\Exception;

use Phpro\ApiProblem\Exception\ApiProblemException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ApiProblemHttpException extends ApiProblemException implements HttpExceptionInterface
{
    public function getStatusCode()
    {
        return $this->code > 0 ? $this->code : Response::HTTP_BAD_REQUEST;
    }

    public function getHeaders()
    {
        return ['Content-Type' => 'application/problem+json'];
    }
}
