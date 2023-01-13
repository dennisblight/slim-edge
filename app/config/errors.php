<?php

use Slim\Exception\HttpException;
use SlimEdge\Exceptions\JWTException;
use SlimEdge\Handlers\JWTErrorHandler;
use SlimEdge\Handlers\SlimHttpHandler;

return [
    'enableErrorHandler'  => true,
    'displayErrorDetails' => true,
    'handlers' => [
        SlimHttpHandler::class => HttpException::class,
        CoreExceptionHandler::class => ResponseException::class,
        FormValidationHandler::class => ValidationException::class,
        JWTErrorHandler::class => JWTException::class,
    ],
];