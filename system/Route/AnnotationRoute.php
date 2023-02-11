<?php

declare(strict_types=1);

namespace SlimEdge\Route;

use Doctrine\Common\Annotations\Reader;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use Slim\Interfaces\RouteInterface;
use SlimEdge\Annotation\Route;
use SlimEdge\Kernel;
use SlimEdge\Paths;

use function SlimEdge\Helpers\get_cache;
use function SlimEdge\Helpers\set_cache;

class AnnotationRoute
{
    protected const CacheKey = 'annotationRoute';

    /**
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * @var bool $enableCache
     */
    private $enableCache;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register()
    {
        if(!$this->registerFromCache()) {
            $resolvedRoutes = $this->getRoutes();
            $this->saveToCache($resolvedRoutes);
            $this->registerResolvedRoutes($resolvedRoutes);
        }
    }

    public function getControllerClasses(): array
    {
        $classes = [];
        foreach(rglob(Paths::App . '/*.php') as $file) {
            if(str_starts_with($file, Paths::App . '/routes/'))
                continue;

            if(str_starts_with($file, Paths::App . '/helpers/'))
                continue;

            if(str_starts_with($file, Paths::App . '/config/'))
                continue;

            $controller = str_replace(
                [Paths::App, '.php', '/'],
                ['App', '', '\\'],
                $file
            );

            if(class_exists($controller)) {
                array_push($classes, $controller);
            }
        }

        return $classes;
    }

    public function getRoutes(): array
    {
        $resolvedRoutes = [];
        $classes = $this->getControllerClasses();
        foreach($classes as $class) {
            $routes = $this->getControllerRoutes($class);
            $resolvedRoutes = array_merge($resolvedRoutes, $routes);
        }

        return $resolvedRoutes;
    }

    public function getControllerRoutes(string $controllerClass): array
    {
        $ref = new ReflectionClass($controllerClass);

        /**
         * @var Reader $reader
         */
        $reader = $this->container->get(Reader::class);

        $baseMiddlewares = $this->getControllerMiddlewares($ref);

        /**
         * @var Route\Group $routeGroup
         */
        $routeGroup = $reader->getClassAnnotation($ref, Route\Group::class);
        $basePath = isset($routeGroup) ? $routeGroup->path : '';
        $actions = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
        $resolvedRoutes = [];
        foreach($actions as $action) {

            $methodRoutes = $this->getMethodRoutes($action, $basePath, $baseMiddlewares);
            $resolvedRoutes = array_merge($resolvedRoutes, $methodRoutes);
        }

        return $resolvedRoutes;
    }

    public function getMethodRoutes(ReflectionMethod $method, string $basePath, array $middlewares): array
    {
        /**
         * @var Reader $reader
         */
        $reader = $this->container->get(Reader::class);
        $annotations = $reader->getMethodAnnotations($method);
        $routes = [];
        foreach($annotations as $annotation) {

            if($annotation instanceof Route) {
                array_push($routes, [
                    $annotation->methods,
                    $this->cleanUrl($basePath . $annotation->path),
                    [$method->getDeclaringClass()->getName(), $method->name],
                    $annotation->name,
                    $annotation->arguments,
                ]);
            }

            elseif($annotation instanceof Route\Middleware) {
                $middlewares = array_merge($middlewares, $annotation->middlewares);
            }
        }

        return array_map(function($item) use ($middlewares) {
            array_push($item, $middlewares);
            return $item;
        }, $routes);
    }

    /**
     * @return string[] Middleware class names
     */
    public function getControllerMiddlewares(ReflectionClass $class): array
    {
        /**
         * @var Reader $reader
         */
        $reader = $this->container->get(Reader::class);

        $middlewares = [];
        $annotations = $reader->getClassAnnotations($class);
        foreach($annotations as $annotation) {
            if($annotation instanceof Route\Middleware) {
                $middlewares = array_merge($middlewares, $annotation->middlewares);
            }
        }
        
        return $middlewares;
    }

    /**
     * @return bool Returns true on success
     */
    protected function registerFromCache(): bool
    {
        $cached = get_cache(self::CacheKey, null, 'route');

        if(!is_null($cached)) {
            $this->registerResolvedRoutes($cached);
            return true;
        }

        return false;
    }

    protected function saveToCache(array $resolvedRoutes): void
    {
        set_cache(self::CacheKey, $resolvedRoutes, 'route');
    }

    protected function registerResolvedRoutes(array $resolvedRoutes): void
    {
        foreach($resolvedRoutes as $item)
        {
            [$methods, $path, $handle, $name, $arguments, $middlewares] = $item;

            $route = $this->mapRoute($methods, $path, $handle);

            foreach($middlewares as $mw) {
                $route->add($mw);
            }

            if(isset($name)) {
                $route->setName($name);
            }

            if(!empty($arguments)) {
                $route->setArguments($arguments);
            }
        }
    }

    /**
     * @param string[] $methods
     * @param string $pattern
     * @param callable|string $callable
     * @return RouteInterface
     */
    protected function mapRoute(array $methods, string $pattern, $callable): RouteInterface
    {
        return Kernel::$app->map($methods, $pattern, $callable);
    }

    private function cleanUrl($url)
    {
        $url = rtrim($url, '/');
        while(strpos($url, '//') !== false)
        {
            $url = str_replace('//', '/', $url);
        }

        return empty($url) ? '/' : $url;
    }
}