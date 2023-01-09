<?php

declare(strict_types=1);

namespace App\Controller;

use SlimEdge\Annotation\Route;
use Laminas\Diactoros\Response;

class Index
{
    /**
     * @Route\Get("/", "index")
     */
    public function indexGet()
    {
        return new Response\JsonResponse(null);
    }

    /**
     * @Route\Get("/example", "example")
     */
    public function exampleGet()
    {
        return new Response\JsonResponse("Hello World!");
    }
}
