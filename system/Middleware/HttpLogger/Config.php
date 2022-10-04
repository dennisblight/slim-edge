<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\HttpLogger;

use Slim\Interfaces\RouteInterface;

class Config
{
    /**
     * @var ?int $maxFileSize
     */
    public $maxFileSize;

    /**
     * @var ?string $path
     */
    public $path;

    /**
     * @var ?string $writer
     */
    public $writer;

    public $routes;

    /**
     * @var Config\Request $logRequest
     */
    public $logRequest;

    /**
     * @var Config\Response $logResponse
     */
    public $logResponse;

    public function __construct($config = null)
    {
        if(!is_null($config)) {
            $this->hydrate($config);
        }
    }

    private function hydrate($config)
    {
        if(isset($config['maxFileSize'])) {
            $this->maxFileSize = strval($config['maxFileSize']);
        }

        if(isset($config['path'])) {
            $this->path = strval($config['path']);
        }

        if(isset($config['writer'])) {
            $this->writer = strval($config['writer']);
        }

        if(isset($config['logRequest'])) {
            $this->logRequest = new Config\Request($config['logRequest']);
        }
        else $this->logRequest = new Config\Request;

        if(isset($config['logResponse'])) {
            $this->logResponse = new Config\Response($config['logResponse']);
        }
        else $this->logResponse = new Config\Response;

        if(isset($config['routes'])) {
            $this->routes = $config['routes'];
        }
    }

    public function getConfigForRoute(RouteInterface $route, string $key)
    {
        if(!empty($route->getName()) && is_array($this->routes)) {
            if(isset($this->routes[$route->getName()][$key])) {
                return $this->routes[$route->getName()][$key];
            }
        }

        return null;
    }
}
