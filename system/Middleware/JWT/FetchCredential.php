<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\JWT;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FetchCredential extends RequireCredential
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        try {
            return parent::process($request, $handler);
        }
        catch(Exception $ex) {
            $request->withAttribute('token', $this->token);
            return $handler->handle($request);
        }
    }
}