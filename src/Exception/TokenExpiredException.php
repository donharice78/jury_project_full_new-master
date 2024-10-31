<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TokenExpiredException extends HttpException
{
    public function __construct(string $message = 'Token has expired', int $statusCode = Response::HTTP_UNAUTHORIZED, \Throwable $previous = null)
    {
        parent::__construct($statusCode, $message, $previous);
    }
}
