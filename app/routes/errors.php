<?php

use App\Controller;

/** @var Slim\App $app */

$app->get('/errors/400', [Controller\Errors::class, 'error400']);
$app->get('/errors/401', [Controller\Errors::class, 'error401']);
$app->get('/errors/403', [Controller\Errors::class, 'error403']);
$app->get('/errors/404', [Controller\Errors::class, 'error404']);
$app->get('/errors/410', [Controller\Errors::class, 'error410']);
$app->get('/errors/413', [Controller\Errors::class, 'error413']);
$app->get('/errors/500', [Controller\Errors::class, 'error500']);
$app->get('/errors/501', [Controller\Errors::class, 'error501']);
