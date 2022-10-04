<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\HttpLogger\Config;

use RuntimeException;
use Slim\Interfaces\RouteInterface;
use SlimEdge\Entity\Collection;

class Request
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
     * @var bool $logQuery
     */
    public $logQuery = true;

    /**
     * @var bool $logFormData
     */
    public $logFormData = true;

    /**
     * @var bool $logBody
     */
    public $logBody = true;

    /**
     * @var bool $logUploadedFiles
     */
    public $logUploadedFiles = true;

    /**
     * @var ?string[] $methods
     */
    public $methods = null;

    /**
     * @var ?string[] $ignoreMethods
     */
    public $ignoreMethods = null;

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

    /**
     * @var ?resource
     */
    public $tempBodyResource = null;

    public function __construct($config = null)
    {
        if(!is_null($config)) {
            $this->hydrate($config);
        }
    }

    public function __destruct()
    {
        $this->closeTemp();
    }

    public function closeTemp()
    {
        if(!is_null($this->tempBodyResource)) {
            fclose($this->tempBodyResource);
        }
    }

    private function hydrate($config)
    {
        if(isset($config['ignoreOnMax'])) {
            $this->ignoreOnMax = boolval($config['ignoreOnMax']);
        }
        
        if(isset($config['logQuery'])) {
            $this->logQuery = boolval($config['logQuery']);
        }
        
        if(isset($config['logFormData'])) {
            $this->logFormData = boolval($config['logFormData']);
        }
        
        if(isset($config['logBody'])) {
            $this->logBody = boolval($config['logBody']);
        }
        
        if(isset($config['logUploadedFiles'])) {
            $this->logUploadedFiles = boolval($config['logUploadedFiles']);
        }

        if(isset($config['maxBody'])) {
            $this->maxBody = intval($config['maxBody']);
        }

        if($methods = $this->getConfigItemArray($config, 'methods')) {
            $this->methods = $methods;
        }

        if($addMethods = $this->getConfigItemArray($config, 'addMethods')) {
            if(is_array($this->methods)) {
                $this->methods = array_merge($this->methods, $addMethods);
            }
            else {
                $this->methods = $addMethods;
            }
        }

        if($ignoreMethods = $this->getConfigItemArray($config, 'ignoreMethods')) {
            $ignoreMethods = array_map('strtoupper', $ignoreMethods);
            if(is_array($this->methods)) {
                $methods = [];
                foreach($this->methods as $method) {
                    if(!in_array($method, $ignoreMethods)) {
                        $methods[] = $method;
                    }
                }

                $this->methods = $methods;
            }
            elseif(is_array($this->ignoreMethods)) {
                $this->ignoreMethods = array_merge($this->ignoreMethods, $ignoreMethods);
            }
            else $this->ignoreMethods = $ignoreMethods;
        }

        if($headers = $this->getConfigItemArray($config, 'headers')) {
            $this->headers = array_map($this->normalizeHeader, $headers);
        }

        if($addHeaders = $this->getConfigItemArray($config, 'addHeaders')) {
            if(is_array($this->headers)) {
                $this->headers = array_merge($this->headers, $addHeaders);
            }
            else {
                $this->headers = $addHeaders;
            }
        }

        if($ignoreHeaders = $this->getConfigItemArray($config, 'ignoreHeaders')) {
            $ignoreHeaders = array_map($this->normalizeHeader, $headers);
            if(is_array($this->headers)) {
                $headers = [];
                foreach($this->headers as $header) {
                    if(!in_array($header, $config)) {
                        $headers[] = $header;
                    }
                }

                $this->headers = $headers;
            }
            elseif(is_array($this->ignoreHeaders)) {
                $this->ignoreHeaders = array_merge($this->ignoreHeaders, $ignoreHeaders);
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
        
        $this->cleanDuplicate();
    }

    private function cleanDuplicate() {
        if(is_array($this->methods)) {
            $this->methods = array_unique($this->methods);
        }
        
        if(is_array($this->ignoreMethods)) {
            $this->ignoreMethods = array_unique($this->ignoreMethods);
        }
        
        if(is_array($this->headers)) {
            $this->headers = array_unique($this->headers);
        }
        
        if(is_array($this->ignoreHeaders)) {
            $this->ignoreHeaders = array_unique($this->ignoreHeaders);
        }
        
        if(is_array($this->routes)) {
            $this->routes = array_unique($this->routes);
        }
        
        if(is_array($this->ignoreRoutes)) {
            $this->ignoreRoutes = array_unique($this->ignoreRoutes);
        }
    }

    public function override($config)
    {
        $this->hydrate($config);
    }

    public function checkMethod($method)
    {
        if(!is_null($this->methods) && !in_array($method, $this->methods)) {
            return false;
        }

        if(!is_null($this->ignoreMethods) && in_array($method, $this->ignoreMethods)) {
            return false;
        }

        return true;
    }

    public function filterHeaders($headers)
    {
        if(!is_null($this->headers)) {
            $newHeaders = [];
            foreach($this->headers as $header) {
                if(isset($headers[$header])) {
                    $newHeaders[$header] = $headers[$header];
                }
            }
        }
        else $newHeaders = $headers;

        if(!is_null($this->ignoreHeaders)) {
            foreach($this->ignoreHeaders as $header) {
                unset($newHeaders[$header]);
            }
        }

        return $newHeaders;
    }

    /**
     * @param RouteInterface $route
     */
    public function checkRoute($route)
    {
        if(is_null($route) || is_null($routeName = $route->getName())) {
            return true;
        }

        if(!is_null($this->routes) && !in_array($routeName, $this->routes)) {
            return false;
        }

        if(!is_null($this->ignoreRoutes) && in_array($routeName, $this->ignoreRoutes)) {
            return false;
        }

        return true;
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
