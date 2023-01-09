<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\HttpLogger;

use RuntimeException;
use Slim\Interfaces\RouteInterface;
use SlimEdge\Entity\Collection;

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
     * @var bool $logErrors
     */
    public $logErrors = false;

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
            $this->maxFileSize = intval($config['maxFileSize']);
        }

        if(isset($config['logErrors'])) {
            $this->logErrors = boolval($config['logErrors']);
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

    public function compileConfig($path)
    {
        $template = file_get_contents(__DIR__ . '/Config.tpl');

        $compiled = str_replace([
            "'{maxFileSize}'",
            "'{logErrors}'",
            "'{path}'",
            "'{writer}'",
            "'{routes}'",
            "'{request.maxBody}'",
            "'{request.ignoreOnMax}'",
            "'{request.logQuery}'",
            "'{request.logFormData}'",
            "'{request.logBody}'",
            "'{request.logUploadedFiles}'",
            "'{request.methods}'",
            "'{request.ignoreMethods}'",
            "'{request.headers}'",
            "'{request.ignoreHeaders}'",
            "'{request.routes}'",
            "'{request.ignoreRoutes}'",
            "'{response.maxBody}'",
            "'{response.ignoreOnMax}'",
            "'{response.logBody}'",
            "'{response.statusCodes}'",
            "'{response.ignoreStatusCodes}'",
            "'{response.headers}'",
            "'{response.ignoreHeaders}'",
            "'{response.routes}'",
            "'{response.ignoreRoutes}'",
        ], [
            $this->varExport($this->maxFileSize, 1),
            $this->varExport($this->logErrors, 1),
            $this->varExport($this->path, 1),
            $this->varExport($this->writer, 1),
            $this->varExport($this->routes, 1),
            $this->varExport($this->logRequest->maxBody, 2),
            $this->varExport($this->logRequest->ignoreOnMax, 2),
            $this->varExport($this->logRequest->logQuery, 2),
            $this->varExport($this->logRequest->logFormData, 2),
            $this->varExport($this->logRequest->logBody, 2),
            $this->varExport($this->logRequest->logUploadedFiles, 2),
            $this->varExport($this->logRequest->methods, 2),
            $this->varExport($this->logRequest->ignoreMethods, 2),
            $this->varExport($this->logRequest->headers, 2),
            $this->varExport($this->logRequest->ignoreHeaders, 2),
            $this->varExport($this->logRequest->routes, 2),
            $this->varExport($this->logRequest->ignoreRoutes, 2),
            $this->varExport($this->logResponse->maxBody, 2),
            $this->varExport($this->logResponse->ignoreOnMax, 2),
            $this->varExport($this->logResponse->logBody, 2),
            $this->varExport($this->logResponse->statusCodes, 2),
            $this->varExport($this->logResponse->ignoreStatusCodes, 2),
            $this->varExport($this->logResponse->headers, 2),
            $this->varExport($this->logResponse->ignoreHeaders, 2),
            $this->varExport($this->logResponse->routes, 2),
            $this->varExport($this->logResponse->ignoreRoutes, 2),
        ], $template);

        if(!file_exists($dir = dirname($path))) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, $compiled);
    }

    private function varExport($value, $indentLevel = 0)
    {
        if(is_scalar($value) || is_null($value)) {
            return var_export($value, true);
        }
        elseif(is_array($value)) {
            $result = "[\n";
            $isList = array_is_list($value);
            foreach($value as $key => $item) {
                $result .= str_repeat('    ', $indentLevel + 1);
                if(!$isList) {
                    $result .= $this->varExport($key);
                    $result .= ' => ';
                }

                $result .= $this->varExport($item, $indentLevel + 1);
                $result .= ",\n";
            }
            return $result . str_repeat('    ', $indentLevel) . ']';
        }
        elseif($value instanceof Collection) {
            return $this->varExport($value->getArrayCopy(), $indentLevel);
        }
        else {
            $type = is_object($value) ? get_class($value) : gettype($value);
            throw new RuntimeException("Could not resolve '$type' parameter value from varExport");
        }
    }
}
