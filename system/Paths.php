<?php

declare(strict_types=1);

namespace SlimEdge;

define('SYS_PATH', __DIR__);
define('APP_PATH', BASEPATH . '/app');
define('STORAGE_PATH', BASEPATH . '/storage');

abstract class Paths
{
    public const App = APP_PATH;

    public const Config = APP_PATH . '/config';

    public const Command = APP_PATH . '/Commands';

    public const Dependencies = APP_PATH . '/dependencies';

    public const Controller = APP_PATH . '/Controller';

    public const Cache = STORAGE_PATH . '/cache';

    public const Helper = APP_PATH . '/helpers';

    public const Route = APP_PATH . '/routes';

    public const Entity = APP_PATH . '/Data';

    public const Middleware = APP_PATH . '/Middleware';

    private function __construct()
    {
    }
}
