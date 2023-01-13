<?php

declare(strict_types=1);

namespace SlimEdge\Handlers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Factory\AppFactory;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

class JWTErrorHandler implements ErrorHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param \UnexpectedValueException $exception
     * @param bool $displayErrorDetails
     * @param bool $logErrors
     * @param bool $logErrorDetails
     */
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface
    {
        $response = AppFactory::determineResponseFactory()->createResponse(401);

        $payload = [
            'code'    => 401,
            'message' => $exception->getMessage(),
        ];

        $response = $response->withHeader('Content-Type', 'application/json');

        $response->getBody()->write(json_encode($payload));

        return $response;
    }
}