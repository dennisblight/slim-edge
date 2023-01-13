<?php

use SlimEdge\Handlers\ErrorsHandler as Errors;

/** @var Slim\App $app */

$app->get('/err/400', [Errors::class, 'error400']);
$app->get('/err/401', [Errors::class, 'error401']);
$app->get('/err/403', [Errors::class, 'error403']);
$app->get('/err/404', [Errors::class, 'error404']);
$app->get('/err/410', [Errors::class, 'error410']);
$app->get('/err/413', [Errors::class, 'error413']);
$app->get('/err/500', [Errors::class, 'error500']);
$app->get('/err/501', [Errors::class, 'error501']);
