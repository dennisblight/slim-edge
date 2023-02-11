<?php

declare(strict_types=1);

namespace SlimEdge\Cors;

use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function SlimEdge\Helpers\get_cache;
use function SlimEdge\Helpers\set_cache;

class Middleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * @var Config $config
     */
    private $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->initConfig($container);
    }

    private function initConfig(ContainerInterface $container)
    {
        $cached = get_cache(Config::class, null, 'config');
        if(!is_null($cached)) {
            $this->config = $cached;
            return;
        }

        $config = $container->get('config.cors');
        $this->config = new Config($config);
        
        set_cache(Config::class, $this->config, 'config');
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $response = $handler->handle($request);

        if($this->config->forRequest($request)->enabled) {
            $analyzer = new Analyzer($this->config, $request);
            $corsHeaders = $analyzer->analyze();
            foreach($corsHeaders as $key => $value)
                $response = $response->withHeader($key, $value);
        }Logger::class;

        return $response;
    }
}