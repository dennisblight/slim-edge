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
            $this->stopwatch->start('application');
        }

        $response = $handler->handle($request);
        if(isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $bootingDuration = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
            $bootingDuration *= 1000;
            $bootingDuration = round($bootingDuration);
        }

        if($hasProfiling) {
            $duration = $this->stopwatch->stop('application')->getDuration();
            $profiles = ['application: ' . $duration];
            if(isset($bootingDuration)) {
                array_unshift($profiles, ['booting:', $bootingDuration]);
            }

            $response = $response->withHeader('X-Profiling', $profiles);
        }

        return $response;
    }
}