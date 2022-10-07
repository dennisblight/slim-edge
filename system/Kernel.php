<?php

declare(strict_types=1);

namespace SlimEdge;

use DI;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use SlimEdge\Commands\RouteList;
use SlimEdge\Entity\Collection;
use SlimEdge\Factory\ConfigFactory;
use SlimEdge\Handlers\Preflight;
use SlimEdge\Middleware\CorsMiddleware;
use SlimEdge\Middleware\RequestPassingMiddleware;
use SlimEdge\Route\AnnotationRoute;
use Symfony\Component\Console\Application;
use Symfony\Component\Stopwatch\Stopwatch;

class Kernel
{
    /**
     * @var ContainerInterface $container
     */
    public static $container;

    /**
     * @var App $app
     */
    public static $app;

    /**
     * @var Application $console
     */
    public static $console;

    /**
     * Current request that passing across controller,
     * this value is set when application add ```RequestPassingMiddleware```
     * which will added by default
     * 
     * @var ServerRequestInterface $request
     */
    public static $request;

    /**
     * @return Application|App Console or Slim app, depend on context.
     */
    public static function boot()
    {
        assert_options(ASSERT_ACTIVE, 1);
        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_EXCEPTION, 1);

        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            if (0 === error_reporting()) {
                return false;
            }

            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        $bs = new static;

        $config = $bs->loadConfig();
        $builder = $bs->createBuilder($config);

        static::$container = $builder->build();
        static::$app = DI\Bridge\Slim\Bridge::create(static::$container);
        $bs->registerMiddleware($config);
        $bs->registerErrorHandler($config);
        $bs->registerRoutes($config);

        if (is_cli()) {
            static::$console = new Application($config['console'] ?? "Slim Edge");
            static::$console->add(new RouteList);
            static::$console->add(new \SlimEdge\Commands\ClearCache);
            return static::$console;
        }

        return static::$app;
    }

    /**
     * Load base config from app/config/config.php file
     */
    public function loadConfig(): array
    {
        $config = [];

        $configFile = Paths::Config . '/config.php';

        $load = function($path) {
            $config = require $path;
            assert(is_array($config), "Config file '$path' must return array");
            return $config;
        };

        if (file_exists($configFile)) {
            $config = $load($configFile);
        }

        if (defined('ENVIRONMENT')) {
            $configFile = Paths::Config . '/config.' . ENVIRONMENT . '.php';
            if (file_exists($configFile)) {
                $config = array_merge($config, $load($configFile));
            }

            $configFile = Paths::Config . '/config/' . ENVIRONMENT . '/config.php';
            if (file_exists($configFile)) {
                $config = array_merge($config, $load($configFile));
            }
        }

        return $config;
    }

    public function createBuilder(array $config)
    {
        $builder = new \DI\ContainerBuilder();

        $autowiring = $config['autowiring'] ?? 'reflection';

        assert(
            in_array($autowiring, ['reflection', 'annotation', 'none']),
            "Autowiring option must be one of 'reflection', 'annotation' or 'none'"
        );

        if ($autowiring === 'reflection') {
            $builder->useAutowiring(true);
            $builder->useAnnotations(false);
        } elseif ($autowiring === 'annotation') {
            $builder->useAutowiring(true);
            $builder->useAnnotations(true);
        } elseif ($autowiring === 'none') {
            $builder->useAutowiring(false);
            $builder->useAnnotations(false);
        }

        $compileContainer = $config['compileContainer'] ?? false;
        assert(
            is_bool($compileContainer),
            "Compile container option must be boolean"
        );

        if ($compileContainer) {
            $builder->enableCompilation(Paths::Cache . '/di');
        }

        $definitions = [
            'config' => DI\create(Collection::class)->constructor($config),
        ];

        $this->registerConfig($definitions);
        $this->registerDependencies($definitions);
        $this->registerHelpers($definitions);

        $builder->addDefinitions($definitions);

        return $builder;
    }

    public function registerConfig(array &$definition): void
    {
        $configDefinition = [];
        $pattern = Paths::Config . '/*.php';
        $offset = strlen(Paths::Config) + 1;
        $trailing = 4;
        foreach (glob($pattern) as $script) {
            $configKey = substr($script, $offset, -$trailing);
            if($configKey !== 'config') {
                $configDefinition[$configKey] = [$script];
            }
        }

        if (defined('ENVIRONMENT')) {
            $pattern = Paths::Config . '/*.' . ENVIRONMENT . '.php';
            $offset = strlen(Paths::Config) + 1;
            $trailing = strlen(ENVIRONMENT) + 5;
            foreach (glob($pattern) as $script) {
                $configKey = substr($script, $offset, -$trailing);
                if ($configKey !== 'config') {

                    if (!isset($configDefinition[$configKey])) {
                        $configDefinition[$configKey] = [];
                    }

                    $configDefinition[$configKey][] = $script;
                }
            }

            $pattern = Paths::Config . '/' . ENVIRONMENT . '/*.php';
            $offset = strlen(Paths::Config) + strlen(ENVIRONMENT)  + 2;
            $trailing = 4;
            foreach (glob($pattern) as $script) {
                $configKey = substr($script, $offset + 1, -$trailing);
                if ($configKey !== 'config') {

                    if (!isset($configDefinition[$configKey])) {
                        $configDefinition[$configKey] = [];
                    }

                    $configDefinition[$configKey][] = $script;
                }
            }
        }

        foreach ($configDefinition as $key => $scripts) {
            $definition['config.' . $key] = DI\factory([ConfigFactory::class, 'create'])
                ->parameter('configFiles', $scripts);
        }
    }

    public function registerDependencies(array &$definition)
    {
        $dependenciesPaths = [
            Paths::Dependencies,
            BASEPATH . '/system/dependencies',
        ];

        $dependencies = [];

        $load = function($path) {
            $result = require $path;
            assert(is_array($result), "Dependency definition must be array");
            return $result;
        };

        foreach($dependenciesPaths as $dependenciesPath)
        {
            foreach(glob($dependenciesPath . '/*.php') as $script)
            {
                $result = $load($script);
                $dependencies[] = $script;
                $definition = array_merge($definition, $result);
            }
        }

        $definition['dependencies'] = $dependencies;
    }

    public function registerHelpers(array &$definition)
    {
        $helpers = [];
        $pattern = Paths::Helper . '/*.php';
        $load = function($path) {
            require $path;
        };

        foreach(rglob($pattern) as $script) {
            $helpers[] = $script;
            $load($script);
        }

        $definition['helpers'] = $helpers;
    }

    public function registerMiddleware(array $config)
    {
        $app = static::$app;
        $app->add(RequestPassingMiddleware::class);

        $middlewares = $config['middleware'] ?? [];
        foreach($middlewares as $middleware) {
            $app->add($middleware);
        }

        $enableBodyParsing = $config['enableBodyParsing'] ?? true;
        if($enableBodyParsing) {
            $app->addBodyParsingMiddleware();
        }

        $enableCors = $config['cors']['enableCors'] ?? false;
        if($enableCors) {
            $app->options('{uri:.+}', Preflight::class)->setName('preflight');
            $app->add(CorsMiddleware::class);
        }

        $app->addRoutingMiddleware();
    }

    public function registerErrorHandler(array $config)
    {
        $config = $config['errors'] ?? [];
        
        $enableErrorHandler = $config['enableErrorHandler'] ?? true;
        if(!$enableErrorHandler) return;

        $app = static::$app;
        $middleware = $app->addErrorMiddleware(
            $config['displayErrorDetails'] ?? false,
            $config['logErrors'] ?? false,
            $config['logErrorDetails'] ?? false
        );

        $handlers = $config['handlers'] ?? [];
        foreach($handlers as $handler => $types) {
            $middleware->setErrorHandler($types, $handler, true);
        }
    }

    public function registerRoutes(array $config)
    {
        $routeCaching = $config['routeCaching'] ?? false;
        if($routeCaching) {
            $routerCacheFile = Paths::Cache . '/routeDispatcher.php';
            static::$app->getRouteCollector()->setCacheFile($routerCacheFile);
        }

        $routes = $config['routes'] ?? [];
        foreach($routes as $route) {
            $routeFile = Paths::Route . '/' . $route . '.php';
            assert(file_exists($routeFile), "Route file '$routeFile' not found");

            $load = function($app) use ($routeFile) {
                require $routeFile;
            };

            $load(static::$app);
        }

        $annotationRouting = $config['annotationRouting'] ?? false;
        if($annotationRouting) {
            $annotationRoute = new AnnotationRoute(static::$container);
            $annotationRoute->register();
        }

        $routeBasePath = $config['routeBasePath'] ?? get_base_path();
        static::$app->setBasePath($routeBasePath);
    }
}
