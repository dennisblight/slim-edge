<?php

use App\Controller;
use Laminas\Diactoros\Response;

$app->get('/', function() {
    return new Response\JsonResponse('OK');
});