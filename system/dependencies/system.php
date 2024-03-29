<?php

declare(strict_types=1);

use SlimEdge\Paths;
use SlimEdge\Kernel;
use SlimEdge\Entity\Collection;
use Psr\SimpleCache\CacheInterface;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Doctrine\Common\Annotations\Reader;
use Psr\Http\Message\ServerRequestInterface;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\Annotations\AnnotationReader;
use Hashids\Hashids;

use function SlimEdge\Helpers\enable_cache;

return [
    'settings'      => \DI\get('config'),
    'config.config' => \DI\get('config'),

    ServerRequestInterface::class => \DI\factory(function() {
        return Kernel::$request;
    }),

    CacheInterface::class => \DI\factory(function() {
        $config = new Phpfastcache\Config\Config(['path' => Paths::Cache]);
        return new Psr16Adapter('Files', $config);
    }),

    CacheItemPoolInterface::class => \DI\factory(function(ContainerInterface $container) {
        return $container->get('cache')->getInternalCacheInstance();
    }),

    'cache' => \DI\get(CacheInterface::class),

    Reader::class => \DI\factory(function(ContainerInterface $container) {
        $reader = new AnnotationReader();
        if(enable_cache('annotation')) {
            return new PsrCachedReader(
                $reader,
                $container->get(CacheItemPoolInterface::class)
            );
        }

        return $reader;
    }),

    'registry' => function() {
        return new Collection();
    },
];