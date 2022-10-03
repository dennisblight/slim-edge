<?php

declare(strict_types=1);

namespace SlimEdge\Handlers;

use Psr\Http\Message\ResponseInterface;

class Preflight
{
    public function __invoke(ResponseInterface $response)
    {
        return $response;
    }
}