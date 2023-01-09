<?php

declare(strict_types=1);

namespace SlimEdge\Libraries;

use Psr\Container\ContainerInterface;
use RuntimeException;
use SlimEdge\Entity\Collection;
use SqlTark\Compiler\BaseCompiler;
use SqlTark\Compiler\MySqlCompiler;
use SqlTark\Compiler\SQLServerCompiler;
use SqlTark\Connection\AbstractConnection;
use SqlTark\Connection\MySqlConnection;
use SqlTark\Connection\SQLServerConnection;
use SqlTark\XQuery;

class Database extends XQuery
{
    /**
     * @var Collection $config
     */
    private $config;

    /**
     * @var XQuery[] $default
     */
    private $connections = [];

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get('config.database');
        parent::__construct(...$this->getDefaultConnection());
    }

    private function getDefaultConnection()
    {
        $default = $this->config->get('default', 'main');
        $this->connections[$default] = $this;

        $config = $this->getConfigForConenction($default);


        $config = $this->config->connections->get($default);
        return [
            $this->resolveDriver($config),
            $this->resolveCompiler($config),
        ];
    }

    private function resolveDriver($config)
    {
        switch($config['driver']) {
            case 'mysql':
            case MySqlConnection::class:
            return new MySqlConnection($config);

            case 'sqlsrv':
            case SQLServerConnection::class:
            return new SQLServerConnection($config);
        }

        if(class_exists($config['driver']) && is_subclass_of($config['driver'], AbstractConnection::class)) {
            return new $config['driver']($config);
        }

        throw new RuntimeException("Could not resolve connection for '{$config['driver']}' driver.");
    }

    private function resolveCompiler($config)
    {
        $driver = $config['compiler'] ?? $config['driver'];
        switch($driver) {
            case 'mysql':
            case MySqlCompiler::class:
            return new MySqlCompiler;

            case 'sqlsrv':
            case SQLServerCompiler::class;
            return new SQLServerCompiler;
        }

        if(class_exists($driver) && is_subclass_of($driver, BaseCompiler::class)) {
            return new $driver($config);
        }

        throw new RuntimeException("Could not resolve query compiler for '{$driver}' driver.");
    }

    private function getConfigForConenction(string $configName)
    {
        if(!$this->config->has('connections')) {
            throw new RuntimeException("Invalid database configuration. Connections not found.");
        }

        if(!$this->config->connections->has($configName)) {
            throw new RuntimeException("Invalid database configuration. Config '$configName' not found.");
        }

        return $this->config->connections->get($configName);
    }

    /**
     * @return XQuery
     */
    public function connection(string $connection)
    {
        if(array_key_exists($connection, $this->connections)) {
            return $this->connections[$connection];
        }

        $config = $this->getConfigForConenction($connection);

        $connection = $this->resolveDriver($config);
        $compiler = $this->resolveCompiler($config);

        return $this->connections[$connection] = new XQuery($connection, $compiler);
    }
}
