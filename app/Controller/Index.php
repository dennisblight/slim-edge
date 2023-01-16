<?php

declare(strict_types=1);

namespace App\Controller;

use App\Data\ExampleForm;
use DI\Annotation\Inject;
use DI\Container;
use SlimEdge\Annotation\Route;
use Laminas\Diactoros\Response;
use SlimEdge\Libraries\Database;
use SlimEdge\Libraries\JWT;

class Index
{

    /**
     * @Inject("db.main2")
     * @var Database
     */
    private $db;

    /**
     * @Inject
     * @var JWT
     */
    private $jwt;

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
     * @Route\Get("/sys/example")
     */
    public function sysXGet(ExampleForm $form)
    {
        return new Response\JsonResponse($form);
    }

    /**
     * @Route\Get("/sysget")
     */
    public function sysget()
    {
        return new Response\JsonResponse($this->jwt->encode([]));
    }
}
