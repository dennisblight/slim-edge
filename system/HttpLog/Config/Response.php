<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog\Config;

class Response
{
    use ConfigTrait;

    /**
     * @var ?int $maxBody
     */
    public $maxBody = null;

    /**
     * @var bool $ignoreOnMax
     */
    public $ignoreOnMax = false;

    /**
     * @var bool $logBody
     */
    public $logBody = true;

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
        if(isset($config['maxBody'])) {
            $this->maxBody = intval($config['maxBody']);
        }

        if(isset($config['ignoreOnMax'])) {
            $this->ignoreOnMax = boolval($config['ignoreOnMax']);
        }

        if($statusCodes = $this->getConfigItemArray($config, 'statusCodes')) {
            $this->statusCodes = $statusCodes;
        }

        if($addStatusCodes = $this->getConfigItemArray($config, 'addStatusCodes')) {
            if(is_array($this->statusCodes)) {
                $this->statusCodes = array_merge($this->statusCodes, $addStatusCodes);
            }
            else $this->statusCodes = $addStatusCodes;
        }

        if($ignoreStatusCodes = $this->getConfigItemArray($config, 'ignoreStatusCodes')) {
            if(is_array($this->statusCodes)) {
                $statusCodes = [];
                foreach($this->statusCodes as $method) {
                    if(!in_array($method, $ignoreStatusCodes)) {
                        $statusCodes[] = $method;
                    }
                }

                $this->statusCodes = $statusCodes;
            }
            elseif(is_array($this->ignoreStatusCodes)) {
                $this->ignoreStatusCodes = array_merge($this->ignoreStatusCodes, $ignoreStatusCodes);
            }
            else $this->ignoreStatusCodes = $ignoreStatusCodes;
        }

        if($headers = $this->getConfigItemArray($config, 'headers')) {
            $this->headers = array_map([$this, 'normalizeHeader'], $headers);
        }

        if($ignoreHeaders = $this->getConfigItemArray($config, 'ignoreHeaders')) {
            $ignoreHeaders = array_map([$this, 'normalizeHeader'], $ignoreHeaders);
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
            if(is_array($this->routes)) {
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

    private function cleanDuplicate()
    {
        if(is_array($this->statusCodes)) {
            $this->statusCodes = array_unique($this->statusCodes);
        }

        if(is_array($this->ignoreStatusCodes)) {
            $this->ignoreStatusCodes = array_unique($this->ignoreStatusCodes);
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

    public function checkStatusCode($statusCode)
    {
        if(!is_null($this->statusCodes)) {

            if(!in_array($statusCode, $this->statusCodes)) {
                return false;
            }
            
            if(!in_array(intdiv($statusCode, 100) . 'xx', $this->statusCodes)) {
                return false;
            }
        }

        if(!is_null($this->ignoreStatusCodes)) {

            if(in_array($statusCode, $this->ignoreStatusCodes)) {
                return false;
            }

            if(in_array(intdiv($statusCode, 100) . 'xx', $this->ignoreStatusCodes)) {
                return false;
            }
        }

        return true;
    }
}
