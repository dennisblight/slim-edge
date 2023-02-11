<?php

declare(strict_types=1);

namespace SlimEdge\Cors;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use SlimEdge\Entity\Collection;
use SlimEdge\Exceptions\ConfigException;

class Config
{
    /**
     * Is cors enabled
     * @var bool $enable
     */
    public $enabled = true;

    /**
     * Which arigins are allowed
     * @var array $allowOrigins
     */
    public $allowOrigins = [ ];

    /**
     * Which headers are allowed
     * @var array $allowHeaders
     */
    public $allowHeaders = [ ];

    /**
     * Which credentials are allowed
     * @var array $allowCredentials
     */
    public $allowCredentials = [ ];

    /**
     * Which headers are allowed
     * @var array $exposeHeaders
     */
    public $exposeHeaders = [ ];

    /**
     * 
     * @var ?int $maxAge
     */
    public $maxAge = null;

    /**
     * Routes specific configuration.
     * Configuration is cascading and not nested
     * @var ?array<string, Config> $routes
     */
    public $routes = null;

    public function __construct($config = null)
    {
        if(!is_null($config)) {
            $this->hydrate($config);
        }
    }

    /**
     * @var ServerRequestInterface $request
     */
    public function forRequest(ServerRequestInterface $request): Config
    {
        $routeName = RouteContext::fromRequest($request)->getRoute()->getName();
        return !is_null($this->routes) && isset($this->routes[$routeName])
            ? $this->routes[$routeName]
            : $this;
    }

    /**
     * @param array $config
     */
    protected function hydrate($config = [], $isRoot = true): void
    {
        if(isset($config['enabled'])) {
            $raw = $config['enabled'];
            if(!is_bool($raw)) {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for 'enabled' value in Cors config.");
            }
            $this->enabled = $raw;
        }

        $this->hydrateIterableValue($config, 'allowOrigins');
        $this->hydrateIterableValue($config, 'allowHeaders', true);
        $this->hydrateIterableValue($config, 'exposeHeaders', true);
        $this->hydrateIterableValue($config, 'allowCredentials', true, false);
        if(isset($config['maxAge'])) {
            $raw = $config['maxAge'];
            if(is_int($raw) || is_null($raw)) {
                $resolved = $raw;
            }
            elseif(is_float($raw)) {
                $resolved = intval($raw);
            }
            elseif(is_string($raw)) {
                $timed = strtotime($raw);
                $resolved = false !== $timed ? ($timed - time()) : intval($raw);
            }
            else {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for 'maxAge' value in Cors config.");
            }

            if($resolved < 0) $resolved = 0;
            elseif(is_infinite($resolved)) {
                throw new ConfigException("Value for 'maxAge' must be positive integer.");
            }

            $this->maxAge = $resolved;
        }

        if($isRoot && isset($config['routes'])) {
            $raw = $config['routes'];
            if(is_array($raw)) {
                $resolved = $raw;
            }
            elseif($raw instanceof Collection) {
                $resolved = $raw->all();
            }
            else {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for 'routes' value in Cors config.");
            }

            $properties = [
                'enabled',
                'allowOrigins',
                'allowHeaders',
                'allowCredentials',
                'exposeHeaders',
                'maxAge',
            ];

            foreach($resolved as $key => $value) {
                foreach($properties as $prop) {
                   !isset($value[$prop]) && $value[$prop] = $this->$prop;
                }

                $resolved[$key] = new Config;
                $resolved[$key]->hydrate($value, false);
            }

            $this->routes = $resolved;
        }
    }

    /**
     * @var array $config
     * @var string $key
     */
    protected function hydrateIterableValue($config, $key, $toLowerCase = false, $allowWildcards = true)
    {
        if(isset($config[$key])) {
            $raw = $config[$key];
            if(is_array($raw)) {
                $resolved = $raw;
            }
            elseif($raw instanceof Collection) {
                $resolved = $raw->all();
            }
            elseif(is_string($raw)) {
                $resolved = [ $raw ];
            }
            elseif(is_null($raw)) {
                $resolved = [ ];
            }
            else {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for '{$key}' value in Cors config.");
            }

            $resolved = array_reduce($resolved, function($acc, $item) use ($toLowerCase) {
                $item = trim($item);
                if($toLowerCase)
                    $item = strtolower($item);

                if($item !== '')
                    array_push($acc, $item);

                return $acc;
            }, []);

            if(false !== ($index = array_search('*', $resolved))) {
                unset($resolved[$index]);
                if($allowWildcards)
                    array_unshift($resolved, '*');
            }

            $this->$key = array_values($resolved);
        }
    }
}