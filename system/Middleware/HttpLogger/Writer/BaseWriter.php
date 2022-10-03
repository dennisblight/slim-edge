<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\HttpLogger\Writer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class BaseWriter
{
    protected $config = [];

    function __construct($config)
    {
        $this->config = $config;
    }

    abstract public function logRequest(ServerRequestInterface $request): ?string;

    abstract public function logResponse(?string $requestResult, ResponseInterface $response);

    public function getMaxLength()
    {
        return array_item($this->config, 'max_length', INF);
    }
}