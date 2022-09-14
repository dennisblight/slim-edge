<?php

declare(strict_types=1);

namespace App\Controller;

use Laminas\Diactoros\Response;
use SlimEdge\Annotation\Route;

class Index
{
    /**
     * @Route\Get("/")
     */
    public function indexGet()
    {
        return new Response\JsonResponse("OK");
    }
}
