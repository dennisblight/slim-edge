<?php

declare(strict_types=1);

namespace SlimEdge\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class ProfilingMiddeware implements MiddlewareInterface
{
    /**
     * @var Stopwatch $stopwatch
     */
    private $stopwatch;

    public function __construct(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;    
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $hasProfiling = $request->getHeaderLine('X-Profiling');

        if($hasProfiling) {
            $bootingDuration = $this->stopwatch->stop('booting')->getDuration();
            $this->stopwatch->start('application');
        }

        $response = $handler->handle($request);

        if($hasProfiling) {
            $duration = $this->stopwatch->stop('application')->getDuration();
            $response = $response->withHeader('X-Profiling', [
                'booting: ' . $bootingDuration,
                'application: ' . $duration
            ]);
        }

        return $response;
    }
}