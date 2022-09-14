<?php

declare(strict_types=1);

namespace SlimEdge\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TrimSlashes implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
		$uri = $request->getUri();
		$path = $uri->getPath();
		if($path != '/' && substr($path, -1) == '/')
		{
			// recursively remove slashes when its more than 1 slash
			while(substr($path, -1) == '/') {
				$path = substr($path, 0, -1);
			}

			// permanently redirect paths with a trailing slash
			// to their non-trailing counterpart
			$uri = $uri->withPath($path);
			$request = $request->withUri($uri);
		}

		return $handler->handle($request);
    }
}