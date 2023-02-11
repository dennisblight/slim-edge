<?php

declare(strict_types=1);

namespace SlimEdge\Entity;

use DI\Annotation\Inject;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

class AbstractForm extends AbstractEntity
{
    /**
     * Fetch from query
     * @var string[] $fetchQuery
     */
    protected static $fetchQuery = [];

    /**
     * Fetch from parsed body
     * @var string[] $fetchBody
     */
    protected static $fetchBody = [];

    /**
     * Fetch from uploaded files in multipart form
     * @var string[] $fetchFile
     */
    protected static $fetchFile = [];

    /**
     * Fetch from URI arguments
     * @var string[] $fetchArgs
     */
    protected static $fetchArgs = [];

    /**
     * Trim each string parameter
     * @var bool $trimStrings
     */
    public static $trimStrings = true;

    /**
     * Remove invisible characters for each string parameter
     * @var bool $removeInvisibleCharacters
     */
    public static $removeInvisibleCharacters = true;

    /**
     * @return static Object from request
     */
    public static function fromRequest(ServerRequestInterface $request)
    {
        $params = static::createParamsFromRequest($request);
        return new static($params, false);
    }

    private static function createParamsFromRequest(ServerRequestInterface $request): array
    {
        $params = [];

        if(!empty(static::$fetchQuery)) {
            $query = $request->getQueryParams();
            foreach(static::$fetchQuery as $field)
                if(isset($query[$field]))
                    $params[$field] = $query[$field];
        }

        if(!empty(static::$fetchBody)) {
            $body = $request->getParsedBody();
            foreach(static::$fetchBody as $field)
                if(isset($body[$field]))
                    $params[$field] = $body[$field];
        }

        if(!empty(static::$fetchFile)) {
            $files = $request->getUploadedFiles();
            foreach(static::$fetchFile as $field)
                if(isset($files[$field]))
                    $params[$field] = $files[$field];
        }

        if(!empty(static::$fetchArgs)) {
            $routeContext = RouteContext::fromRequest($request);
            $routingResult = $routeContext->getRoutingResults();
            $args = $routingResult->getRouteArguments();
            foreach(static::$fetchArgs as $field)
                if(isset($args[$field]))
                    $params[$field] = $args[$field];
        }

        return $params;
    }

    /**
     * @Inject
     */
    public function mergeRequest(ServerRequestInterface $request)
    {
        $params = static::createParamsFromRequest($request);
        $this->merge($params);
    }

    public function offsetSet($key, $value): void
    {
        if(is_string($value)) {

            if(static::$trimStrings) {
                $value = trim($value);
            }

            if(static::$removeInvisibleCharacters) {
                $value = remove_invisible_characters($value);
            }
        }

        parent::offsetSet($key, $value);
    }
}
