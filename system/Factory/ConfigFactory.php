<?php

declare(strict_types=1);

namespace SlimEdge\Factory;

use SlimEdge\Entity\Collection;

final class ConfigFactory
{
    public const OptionAsRecursiveCollection = 1;
    public const OptionAsCollection = 2;
    public const OptionAsArray = 3;

    public static function create($configFiles = [], $option = self::OptionAsRecursiveCollection)
    {
        $config = [];
        foreach ($configFiles as $script) {
            $_config = require $script;
            assert(is_array($_config), "Config file '{$script}' must return an array");
            $config = array_merge_deep($config, $_config);
        }

        return $option !== self::OptionAsArray
            ? new Collection($config, $option !== self::OptionAsCollection)
            : $config
        ;
    }

    private function __construct()
    {
        /* This class is intended to declared as final */
    }
}