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
        $response = $handler->handle($request);

        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();

        $methods = $routingResults->getAllowedMethods();
        if(!empty($methods)) {
            $response = $response->withHeader('Access-Control-Allow-Methods', implode(',', $methods));
        }

        $requestOrigin = $request->getHeaderLine('Origin');
        if(!empty($requestOrigin)) {
            $origin = (string) $request->getUri()->withPath('');

            if($this->config->has('allowOrigins')) {
                $allowOrigins = (array) $this->config->allowOrigins;

                if(in_array('*', $allowOrigins)) {
                    $origin = '*';
                }
                elseif(in_array($requestOrigin, $allowOrigins)) {
                    $origin = $requestOrigin;
                }
            }

            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }

        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        if(!empty($requestHeaders)) {
            if($this->config->has('allowHeaders')) {
                $allowHeaders = $this->config->allowHeaders;
                $requestHeaders = array_map('trim', explode(',', $requestHeaders));
                $requestHeaders = join(', ', array_intersect($allowHeaders->all(), $requestHeaders));
            }
            if(!empty($requestHeaders))
                $response = $response->withHeader('Access-Control-Allow-Headers', $requestHeaders);
        }

        if($this->config->has('exposeHeaders')) {
            $exposeHeaders = $this->config->exposeHeaders;
            $responseheaders = array_keys($response->getHeaders());
            $exposeHeaders = join(', ', array_intersect($exposeHeaders->all(), $responseheaders));
            if(!empty($exposeHeaders))
                $response = $response->withHeader('Access-Control-Expose-Headers', $exposeHeaders);
        }

        return $response;
    }
}