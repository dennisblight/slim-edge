<?php

declare(strict_types=1);

namespace App\Controller;

use Laminas\Diactoros\Response;

class Errors
{
    public function error403()
    {
        return new Response\JsonResponse([
            'code' => 403,
            'message' => "You don't have permission to access this resource.",
        ], 403);
    }
}