<?php

declare(strict_types=1);

use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteContext;
use SlimEdge\Kernel;

if (!function_exists('container'))
{
    function container(?string $key = null)
    {
        $container = Kernel::$container;

        if(is_null($key)) {
            return $container;
        }

        if($container->has($key)) {
            return $container->get($key);
        }

        return null;
    }
}

if (!function_exists('route'))
{
    function route(?string $name = null): ?RouteInterface
    {
        if(is_null($name)) {
            return RouteContext::fromRequest(Kernel::$request)->getRoute();
        }

        try {
            $routeCollector = Kernel::$app->getRouteCollector();
            return $routeCollector->getNamedRoute($name);
        }
        catch(\RuntimeException $ex) { /** Ignored */ }

        return null;
    }
}