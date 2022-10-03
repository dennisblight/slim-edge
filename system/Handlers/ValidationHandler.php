<?php

declare(strict_types=1);

namespace SlimEdge\Handlers;

use Slim\Interfaces\ErrorHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Respect\Validation\Exceptions\NestedValidationException;
use Slim\Factory\AppFactory;
use Throwable;

class ValidationHandler implements ErrorHandlerInterface
{
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface
    {
        $payload = [
            'code'    => 1000,
            'message' => 'You are not permitted to perform the requested operation.',
        ];

        if($exception instanceof NestedValidationException)
        {
            $payload['errors'] = $exception->getMessages();
        }

        $response = AppFactory::determineResponseFactory()->createResponse(
            403, '403 Forbidden'
        );

        $response = $response->withHeader('Content-Type', 'application/json');

        $response->getBody()->write(json_encode($payload));

        return $response;
    }
}