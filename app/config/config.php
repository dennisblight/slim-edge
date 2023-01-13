<?php

return [
    'enableCache'      => $_SERVER['ENV'] !== 'development',
    'compileContainer' => $_SERVER['ENV'] !== 'development',

    'annotationRouting' => true,
    'enableBodyParsing' => true,
    'autowiring' => 'annotation',

    'timezone' => 'Asia/Jakarta',

    'appKey'     => '',
    'devKey'     => '',
    'appName'    => 'Slim Edge',
    'appVersion' => '1.0',

    'middleware' => [
        SlimEdge\Middleware\TrimSlashes::class,
        SlimEdge\Middleware\CorsMiddleware::class,
        SlimEdge\Middleware\ProfilingMiddeware::class,
        SlimEdge\Middleware\HttpLogger\HttpLoggerMiddleware::class,
    ],

    'errors' => require 'errors.php',

    'cors' => [
        'enableCors' => true,
        'allowOrigins' => 'https://www.example.com',
        'allowHeaders' => [
            'X-Requested-With', 'Content-Type', 'Accept', 'Origin', 'Authorization'
        ],
        'allowCredentials' => true,
    ],

    'routes' => [
        'main',
        'errors',
    ],

    'autoloadCommands' => false,
    'commands' => [ ],
];