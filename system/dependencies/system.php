<?php

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\Annotations\Reader;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use SlimEdge\Entity\Collection;
use SlimEdge\Kernel;
use SlimEdge\Paths;

use function SlimEdge\Helpers\enable_cache;

return [
    'settings'      => \DI\get('config'),
    'config.config' => \DI\get('config'),

    ServerRequestInterface::class => \DI\factory(function() {
        return Kernel::$request;
    }),

    CacheInterface::class => \DI\Factory(function() {
        $config = new Phpfastcache\Config\Config(['path' => Paths::Cache]);
        return new Psr16Adapter('Files', $config);
    }),

    CacheItemPoolInterface::class => \DI\Factory(function(ContainerInterface $container) {
        return $container->get('cache')->getInternalCacheInstance();
    }),

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

    'cache' => \DI\get(CacheInterface::class),
];