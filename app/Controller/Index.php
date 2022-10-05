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
     * @Route\Post("/")
     */
    public function indexGet(ServerRequestInterface $request)
    {
        // $body = $request->getBody();
        return new Response\JsonResponse($_POST);
    }

    /**
     * @Route\Get("/example", "example")
     */
    public function exampleGet()
    {
        return new Response\JsonResponse("Hello World!");
    }
}
