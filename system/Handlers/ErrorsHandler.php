<?php

declare(strict_types=1);

namespace SlimEdge\Handlers;

use Laminas\Diactoros\Response;

class ErrorsHandler
{
    public function error400()
    {
        return new Response\JsonResponse([
            'code' => 400,
            'message' => "The server cannot or will not process the request due to an apparent client error.",
        ], 400);
    }

    public function error401()
    {
        return new Response\JsonResponse([
            'code' => 401,
            'message' => "The request requires valid user authentication.",
        ], 401);
    }

    public function error403()
    {
        return new Response\JsonResponse([
            'code' => 403,
            'message' => "You don't have permission to access this resource.",
        ], 403);
    }

    public function error404()
    {
        return new Response\JsonResponse([
            'code' => 404,
            'message' => "You don't have permission to access this resource.",
        ], 404);
    }

    public function error410()
    {
        return new Response\JsonResponse([
            'code' => 410,
            'message' => "The target resource is no longer available at the origin server.",
        ], 410);
    }

    public function error413()
    {
        return new Response\JsonResponse([
            'code' => 413,
            'message' => "The amount of data provided in the request exceeds the capacity limit.",
        ], 413);
    }

    public function error500()
    {
        return new Response\JsonResponse([
            'code' => 500,
            'message' => "Unexpected condition encountered preventing server from fulfilling request.",
        ], 500);
    }

    public function error501()
    {
        return new Response\JsonResponse([
            'code' => 501,
            'message' => "The server does not support the functionality required to fulfill the request.",
        ], 501);
    }
}
