<?php

declare(strict_types=1);

namespace SlimEdge\Middleware\HttpLogger\Writer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimEdge\Middleware\HttpLogger\Config;

abstract class BaseWriter
{
    /**
     * @var Config $config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public abstract function writeLog(array $logData);
}