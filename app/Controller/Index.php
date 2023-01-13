<?php

declare(strict_types=1);

namespace App\Controller;

use DI\Annotation\Inject;
use SlimEdge\Annotation\Route;
use Laminas\Diactoros\Response;
use SlimEdge\Libraries\Database;

class Index
{

    /**
     * @Inject("db.main2")
     * @var Database
     */
    private $db;

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
        return new Response\JsonResponse(
            $this->db->from('users')->getAll()
        );
    }
}
