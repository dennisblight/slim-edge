<?php

namespace SlimEdge\Helpers;

use SlimEdge\Entity\Collection;
use SlimEdge\Exceptions\ConfigException;
use SlimEdge\Kernel;

if(! function_exists('SlimEdge\Helpers\enable_cache'))
{
    function enable_cache(string $scope): bool
    {
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
        throw new ConfigException("Could not resolve '$class' for config enableCache");
    }
}