<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog\Config;

use Slim\Interfaces\RouteInterface;
use SlimEdge\Entity\Collection;

trait ConfigTrait
{
    public function override($config)
    {
        $this->hydrate($config);
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

    public function checkRoute(RouteInterface $route)
    {
        if(is_null($route)) {
            return true;
        }

        $routeName = $route->getName();
        if(is_null($routeName)) {
            $routeAction = $route->getCallable();
            if(is_string($routeAction)) {
                $routeName = $routeAction;
            }
            elseif(is_array($routeAction)) {
                $routeName = join(':', $routeAction);
            }
            elseif(is_object($routeAction)) {
                $routeName = get_class($routeAction);
            }
        }

        if(is_null($routeName)) {
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

    public function getConfigItemArray($config, $key): ?array
    {
        if(isset($config[$key])) {
            $configItem = $config[$key];
            if(is_array($configItem)) {
                return $configItem;
            }
            elseif($configItem instanceof Collection) {
                $configItem = $configItem->all();
            }
            elseif(is_string($configItem)) {
                $configItem = [$configItem];
            }
            else {
                throw new \RuntimeException("Could not resolve '{$key}' value from config");
            }

            return $configItem;
        }

        return null;
    }

    public function normalizeHeader($value)
    {
        $value = strtolower($value);
        $value = str_replace('_', '-', $value);
        return $value;
    }
}