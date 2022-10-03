<?php

declare(strict_types=1);

namespace SlimEdge\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use SlimEdge\Entity\Collection;

class CorsMiddleware implements MiddlewareInterface
{
    /** @var Collection $config */
    private $config;

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get('config')->cors ?? new Collection();
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();
        $methods = $routingResults->getAllowedMethods();
        
        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        if($this->config->has('allowHeaders'))
        {
            $allowHeaders = $this->config->allowHeaders;
            $requestHeaders = array_map('trim', explode(',', $requestHeaders));
            $requestHeaders = join(', ', array_intersect($allowHeaders->all(), $requestHeaders));
        }

        $origin = '*';
        if($this->config->has('allowOrigins')) {
            $requestOrigin = $request->getHeaderLine('origin');
            $allowOrigins = (array) $this->config->allowOrigins;

            if(in_array('*', $allowOrigins)) {
                $origin = '*';
            }
            elseif(in_array($requestOrigin, $allowOrigins)) {
                $origin = $requestOrigin;
            }
            else {
                $origin = (string) $request->getUri()->withPath('');
            }
        }

        $response = $handler->handle($request);

        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        $response = $response->withHeader('Access-Control-Allow-Methods', implode(',', $methods));
        $response = $response->withHeader('Access-Control-Allow-Headers', $requestHeaders);
        
        return $response;
    }
}