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
        return new Response\JsonResponse("Hello World!");
    }

    /**
     * @Route\Get("/example")
     */
    public function exampleGet()
    {
        return new Response\JsonResponse("Example endpoint");
    }

    /**
     * @Route\Get("/system/example")
     */
    public function sysXGet()
    {
        return new Response\JsonResponse("sys Example endpoint");
    }
}
