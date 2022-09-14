<?php

declare(strict_types=1);

namespace SlimEdge\Entity;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

class AbstractForm extends AbstractEntity
{
    /**
     * Fetch from query parameter
     */
    public const FetchQuery  = 0x01;

    /**
     * Fetch from parsed body parameter
     */
    public const FetchPost   = 0x02;

    /**
     * Fetch from uploaded files in multipart form
     */
    public const FetchFiles  = 0x04;

    /**
     * Fetch from URL parameter
     */
    public const FetchParams = 0x08;

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

    public static $fetchOptions = self::FetchQuery | self::FetchPost;

    /**
     * @return static Object from request
     */
    public static function fromRequest(ServerRequestInterface $request): AbstractForm
    {
        $params = [];

        if(static::$fetchOptions & self::FetchQuery) {
            $params = array_merge($params, $request->getQueryParams());
        }

        if(static::$fetchOptions & self::FetchPost) {
            $params = array_merge($params, $request->getParsedBody());
        }

        if(static::$fetchOptions & self::FetchFiles) {
            $params = array_merge($params, $request->getUploadedFiles());
        }

        if(static::$fetchOptions & self::FetchParams) {
            $routeContext = RouteContext::fromRequest($request);

            $routingResult = $routeContext->getRoutingResults();
            $params = array_merge($params, $routingResult->getRouteArguments());
        }

        return new static($params);
    }

    public function offsetSet($key, $value): void
    {
        if(is_string($value)) {

            if(static::$trimStrings) {
                $value = trim($value);
            }

            if(static::$removeInvisibleCharacters) {
                $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $value);
            }
        }

        parent::offsetSet($key, $value);
    }
}
