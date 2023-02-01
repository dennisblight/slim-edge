<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog\Writer;

use SlimEdge\HttpLog\Config;

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