<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\HttpLogger\Config;

use RuntimeException;
use SlimEdge\Entity\Collection;

class Response
{
    /**
     * @var ?int $maxBody
     */
    public $maxBody = null;

    /**
     * @var bool $ignoreOnMax
     */
    public $ignoreOnMax = false;

    /**
     * @var ?string[] $statusCodes
     */
    public $statusCodes = null;

    /**
     * @var ?array $ignoreStatusCodes
     */
    public $ignoreStatusCodes = null;

    /**
     * @var ?string[] $headers
     */
    public $headers = null;

    /**
     * @var ?string[] $ignoreHeaders
     */
    public $ignoreHeaders = null;

    /**
     * @var ?string[] $routes
     */
    public $routes = null;

    /**
     * @var ?string[] $ignoreRoutes
     */
    public $ignoreRoutes = null;

    public function __construct($config = null)
    {
        if(!is_null($config)) {
            $this->hydrate($config);
        }
    }

    private function hydrate($config)
    {
        if(isset($config['ignoreOnMax'])) {
            $this->ignoreOnMax = boolval($config['ignoreOnMax']);
        }

        if(isset($config['maxBody'])) {
            $this->maxBody = intval($config['maxBody']);
        }

        if($statusCodes = $this->getConfigItemArray($config, 'statusCodes')) {
            $this->statusCodes = $statusCodes;
        }

        if($ignoreStatusCodes = $this->getConfigItemArray($config, 'ignoreStatusCodes')) {
            if($this->statusCodes) {
                $statusCodes = [];
                foreach($this->statusCodes as $method) {
                    if(!in_array($method, $ignoreStatusCodes)) {
                        $statusCodes[] = $method;
                    }
                }

                $this->statusCodes = $statusCodes;
            }
            else $this->ignoreStatusCodes = $ignoreStatusCodes;
        }

        if($headers = $this->getConfigItemArray($config, 'headers')) {
            $this->headers = array_map($this->normalizeHeader, $headers);
        }

        if($ignoreHeaders = $this->getConfigItemArray($config, 'ignoreHeaders')) {
            if($this->headers) {
                $headers = [];
                foreach($this->headers as $header) {
                    $header = $this->normalizeHeader($header);
                    if(!in_array($header, $config)) {
                        $headers[] = $header;
                    }
                }

                $this->headers = $headers;
            }
            else $this->ignoreHeaders = $ignoreHeaders;
        }

        if($routes = $this->getConfigItemArray($config, 'routes')) {
            $this->routes = $routes;
        }

        if($ignoreRoutes = $this->getConfigItemArray($config, 'ignoreRoutes')) {
            if($this->routes) {
                $routes = [];
                foreach($this->routes as $route) {
                    if(!in_array($route, $ignoreRoutes)) {
                        $routes[] = $route;
                    }
                }

                $this->routes = $routes;
            }
            else $this->ignoreRoutes = $ignoreRoutes;
        }
    }

    private function getConfigItemArray($config, $key): ?array
    {
        if(isset($config[$key])) {
            $configItem = $config[$key];
            if($configItem instanceof Collection) {
                $configItem = $configItem->all();
            }
            elseif(is_string($configItem)) {
                $configItem = [$configItem];
            }
            else {
                throw new RuntimeException("Could not resolve '$key' value from config");
            }

            return $configItem;
        }
        return null;
    }

    private function normalizeHeader($value)
    {
        $value = strtolower($value);
        $value = str_replace('_', '-', $value);
        return $value;
    }
}
