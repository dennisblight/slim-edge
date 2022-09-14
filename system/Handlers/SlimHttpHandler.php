<?php

declare(strict_types=1);

namespace SlimEdge\Handlers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

class SlimHttpHandler implements ErrorHandlerInterface
{
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface
    {
        if($exception instanceof HttpMethodNotAllowedException)
        {
            $allowedMethods = $exception->getAllowedMethods();
            if(count($allowedMethods) === 1 && $allowedMethods[0] === 'OPTIONS')
            {
                return $this->__invoke(
                    $request,
                    new HttpNotFoundException($request),
                    $displayErrorDetails,
                    $logErrors,
                    $logErrorDetails
                );
            }
        }

        $responseCode = $exception->getCode();
        $reasonPhrase = $exception->getMessage();
        $response = AppFactory::determineResponseFactory()->createResponse(
            $responseCode,
            $reasonPhrase
        );

        $payload = [
            'code'    => $exception->getCode(),
            'message' => $exception->getMessage(),
        ];

        if($exception instanceof HttpMethodNotAllowedException)
        {
            $payload['allowedMethods'] = $exception->getAllowedMethods();
        }

        $response = $response->withHeader('Content-Type', 'application/json');

        $response->getBody()->write(json_encode($payload));

        return $response;
    }
}