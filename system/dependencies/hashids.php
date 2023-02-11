<?php

use DI\Factory\RequestedEntry;
use Hashids\Hashids;

use function SlimEdge\Helpers\load_config;

$dependencies = [
    'hashids' => DI\get(Hashids::class),

    Hashids::class => \DI\factory(function($config) {
        $config = $config->get('_default');
        $salt       = $config->get('salt', '');
        $length     = $config->get('length', 0);
        $characters = $config->get('characters', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');

        return new Hashids($salt, $length, $characters);
    })->parameter('config', \DI\get('config.hashids')),
];

$hashidsConfig = load_config('hashids');
foreach($hashidsConfig as $key => $connection) {
    if($key !== '_default') {
        $dependencies['hashids.' . $key] = DI\factory(function(RequestedEntry $entry, $config) {
            [, $key] = explode('.', $entry->getName());
            $default = $config->get('_default');
            $config = $config->get($key);
            $salt       = $config->get('salt') ?? $default->get('salt', '');
            $length     = $config->get('length') ?? $default->get('length', 0);
            $characters = $config->get('characters') ?? $default->get('characters', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');

            return new Hashids($salt, $length, $characters);
        })->parameter('config', \DI\get('config.hashids'));
    }
}

return $dependencies;