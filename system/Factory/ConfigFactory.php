<?php

declare(strict_types=1);

namespace SlimEdge\Factory;

use SlimEdge\Entity\Collection;

abstract class ConfigFactory
{
    public static function create($configFiles = [], $asPlainArray = false)
    {
        $config = [];
        foreach ($configFiles as $script) {
            $_config = require $script;
            assert(is_array($_config), "Config file '$script' must return array");
            $config = array_merge($config, $_config);
        }

        if($asPlainArray) {
            return $config;
        }

        return new Collection($config);
    }

    private function __construct()
    {
    }
}