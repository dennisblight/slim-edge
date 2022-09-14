<?php

use SlimEdge\Handlers\CoreExceptionHandler;
use SlimEdge\Handlers\FormValidationHandler;
use SlimEdge\Handlers\SlimHttpHandler;
use SlimEdge\Exceptions\ResponseException;
use Respect\Validation\Exceptions\ValidationException;
use Slim\Exception\HttpException;

return [
    'compileContainer'  => false,
    'cacheContainer'    => false,
    
    'cacheAnnotation'   => false,

    'enableCache' => false, // ['route', 'entity', 'container']

    'annotationRouting' => true,
    'enableBodyParsing' => true,

    'timezone' => 'Asia/Jakarta',

    'appKey'     => '',
    'devKey'     => '',
    'appName'    => 'Slim Edge',
    'appVersion' => '1.0',

    'password' => [
        'algo' => PASSWORD_BCRYPT,
        'cost' => 11,
    ],

    'middleware' => [
        SlimEdge\Middleware\TrimSlashes::class,
        SlimEdge\Middleware\CorsMiddleware::class,
    ],

    'errors' => [
        'enableErrorHandler'  => true,
        'displayErrorDetails' => true,
        'handlers' => [
            SlimHttpHandler::class => HttpException::class,
            CoreExceptionHandler::class => ResponseException::class,
            FormValidationHandler::class => ValidationException::class,
        ],
    ],

    'cors' => [
        'enableCors' => true,
        'allowOrigins' => 'https://www.example.com',
        'allowHeaders' => [
            'X-Requested-With', 'Content-Type', 'Accept', 'Origin', 'Authorization'
        ],
        'allowCredentials' => true,
    ],

    'routes' => [
        'main'
    ],

    'autoloadCommands' => false,
    'commands' => [
        App\Commands\RouteList::class,
    ],
];