<?php

declare(strict_types=1);

namespace SlimEdge\Factory;

use SlimEdge\Entity\Collection;

abstract class ConfigFactory
{
    public const OptionAsRecursiveCollection = 1;
    public const OptionAsCollection = 2;
    public const OptionAsArray = 3;

    public static function create($configFiles = [], $option = self::OptionAsRecursiveCollection)
    {
        $config = [];
        foreach ($configFiles as $script) {
            $_config = require $script;
            assert(is_array($_config), "Config file '{$script}' must return array");
            $config = array_merge_deep($config, $_config);
        }

        switch($option) {
            case self::OptionAsArray: return $config;
            case self::OptionAsCollection: return new Collection($config, false);
            default: return new Collection($config);
        }
    }

    private function __construct()
    {
        /* This class is intended to declared as final */
    }
}