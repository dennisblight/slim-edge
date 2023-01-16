<?php

namespace SlimEdge\Helpers;

use SlimEdge\Entity\Collection;
use SlimEdge\Exceptions\ConfigException;
use SlimEdge\Kernel;
use SlimEdge\Paths;

if(! function_exists('SlimEdge\Helpers\enable_cache'))
{
    function enable_cache(string $scope): bool
    {
        if(is_cli()) {
            return false;
        }

        /**
         * @var Collection $config
         */
        $config = Kernel::$container->get('config');

        $cacheEnabled = $config->get('enableCache', false);
        if(is_bool($cacheEnabled)) {
            return $cacheEnabled;
        }

        if(is_array($cacheEnabled)) {
            return in_array($scope, $cacheEnabled, true);
        }

        if($cacheEnabled instanceof Collection) {
            return $cacheEnabled->exists($scope, true);
        }

        if(is_string($cacheEnabled)) {
            return $cacheEnabled === $scope;
        }
        
        $class = is_object($cacheEnabled) ? get_class($cacheEnabled) : gettype($cacheEnabled);
        throw new ConfigException("Could not resolve '{$class}' for config enableCache");
    }
}

if(!function_exists('SlimEdge\Helpers\uuid_format'))
{
    function uuid_format($uuid)
    {
        if(strlen($uuid) < 32) {
            $uuid = str_pad($uuid, 32, '0');
        }

        return substr($uuid, 0, 8)
            . '-' . substr($uuid, 8, 4)
            . '-' . substr($uuid, 12, 4)
            . '-' . substr($uuid, 16, 4)
            . '-' . substr($uuid, 20, 12);
    }
}

if(!function_exists('SlimEdge\Helpers\load_config'))
{
    function load_config($configName = 'config')
    {
        $config = [];

        $configFile = Paths::Config . "/{$configName}.php";

        $load = function($path) {
            $config = require $path;
            assert(is_array($config), "Config file '{$path}' must return array");
            return $config;
        };

        if (file_exists($configFile)) {
            $config = $load($configFile);
        }

        if (defined('ENVIRONMENT')) {
            $configFile = Paths::Config . "/{$configName}." . ENVIRONMENT . '.php';
            if (file_exists($configFile)) {
                $config = array_merge_recursive($config, $load($configFile));
            }

            $configFile = Paths::Config . '/' . ENVIRONMENT . "/{$configName}.php";
            if (file_exists($configFile)) {
                $config = array_merge_recursive($config, $load($configFile));
            }
        }

        return $config;
    }
}
