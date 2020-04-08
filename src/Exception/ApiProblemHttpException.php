<?php

declare(strict_types=1);

namespace Phpro\ApiProblemBundle\Exception;

use Phpro\ApiProblem\ApiProblemInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiProblemHttpException extends HttpException
{
    private $apiProblem;

    public function __construct(ApiProblemInterface $apiProblem)
    {
        $data = $apiProblem->toArray();
        $message = $data['detail'] ?? ($data['title'] ?? '');
        $code = (int) ($data['status'] ?? 0);

        parent::__construct($code, $message);
        $this->apiProblem = $apiProblem;
    }

    public function getApiProblem(): ApiProblemInterface
    {
        return $this->apiProblem;
    }

    public function getStatusCode()
    {
        return parent::getStatusCode() > 0 ? parent::getStatusCode() : Response::HTTP_BAD_REQUEST;
    }

    public function getHeaders()
    {
        return ['Content-Type' => 'application/problem+json'];
    }
}
