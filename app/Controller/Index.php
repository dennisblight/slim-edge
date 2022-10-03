<?php

declare(strict_types=1);

namespace App\Controller;

use SlimEdge\Annotation\Route;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;

class Index
{
    /**
     * @Route\Get("/", "index")
     */
    public function indexGet()
    {
        return new Response\JsonResponse("OK");
    }

    /**
     * @Route\Get("/example", "example")
     * @Route\Post("/example")
     */
    public function exampleGet()
    {
        return new Response\JsonResponse("Hello World!");
    }
}
