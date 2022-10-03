<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\HttpLogger;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use SlimEdge\Entity\Collection;

class HttpLoggerMiddleware implements MiddlewareInterface
{
    /**
     * @var Collection $config
     */
    private $config;

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get('config.http_logger');
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $route = RouteContext::fromRequest($request)->getRoute();;
        if(!is_null($route) && $route->getArgument('ignoreHttpLog')) {
            return $handler->handle($request);
        }

        $writerClass = $this->config->get('writer');
        if(is_subclass_of($writerClass, Writer\BaseWriter::class)) {
            /** @var Writer\BaseWriter $writer */
            $writer = new $writerClass($this->config);
            $result = $writer->logRequest($request);

            $finalResponse = $handler->handle($request);

            $writer->logResponse($result, $finalResponse);
            return $finalResponse;
        }

        return $handler->handle($request);
    }
}