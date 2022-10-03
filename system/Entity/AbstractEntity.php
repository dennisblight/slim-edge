<?php

declare(strict_types=1);

namespace SlimEdge\Entity;

use Psr\SimpleCache\CacheInterface;
use SlimEdge\Annotation\Reader\EntityReader;
use SlimEdge\Exceptions\EntityException;
use SlimEdge\Helpers;
use SlimEdge\Kernel;

class AbstractEntity extends AbstractCollection
{
    /**
     * @var ?array $properties
     */
    protected static $properties;

    /**
     * @var ?array $accessors
     */
    protected static $accessors;

    /**
     * @var ?array $mutators
     */
    protected static $mutators;

    /**
     * @var array $resolves
     */
    private static $resolves = [];

    public function __construct($data = [])
    {
        static::resolveBehavior();
        parent::__construct($data);
    }

    public function offsetGet($key): mixed
    {
        if(array_key_exists($key, static::$accessors)) {
            return $this->access(static::$accessors[$key]);
        }

        return parent::offsetGet($key);
    }

    public function offsetSet($key, $value): void
    {
        if(!array_key_exists($key, static::$properties)) {
            return;
        }

        $property = static::$properties[$key];
        if(!is_null($property)) {
            [$type, $nullable] = $property;
            $value = $this->cast($type, $value, $nullable);
        }

        if(array_key_exists($key, static::$mutators)) {
            $value = $this->mutate(static::$mutators[$key], $value);
        }

        parent::offsetSet($key, $value);
    }

    protected function access($accessor)
    {
        if(method_exists($this, $accessor)) {
            return call_user_func([$this, $accessor]);
        }

        if(is_callable($accessor)) {
            return call_user_func($accessor);
        }

        throw new EntityException(
            "Could not resolve accessor '$accessor'"
        );
    }

    protected function mutate($type, $value)
    {
        if(method_exists($this, $type)) {
            return call_user_func([$this, $type], $value);
        }

        if(is_callable($type)) {
            return call_user_func($type, $value);
        }

        throw new EntityException(
            "Could not resolve mutator type '$type'"
        );
    }

    protected function cast($type, $value, $nullable = false)
    {
        if($nullable && is_null($value)) {
            return null;
        }

        switch($type) {
            case 'bool':
            case 'boolean':
                return Helpers\cast_boolean($value);
            case 'string':
                return Helpers\cast_string($value);
            case 'int':
            case 'integer':
                return Helpers\cast_integer($value);
            case 'float':
            case 'real':
            case 'double':
                return Helpers\cast_float($value);
            case 'date':
                return Helpers\cast_date($value);
            case 'time':
                return Helpers\cast_time($value);
            case 'datetime':
                return Helpers\cast_datetime($value);
            case 'array':
                return (array) $value;
            case 'object':
                return (object) $value;
            case 'collection':
                return new Collection($value);
        }

        if(class_exists($type) && is_subclass_of($type, AbstractEntity::class)) {
            return new $type($value);
        }

        throw new EntityException(
            "Could not resolve cast type '$type'"
        );
    }

    public static function resolveBehavior()
    {
        if(!static::tryResolveFromRegistry() && !static::tryResolveFromCache()) {
            static::resolveAnnotations();
            static::saveToCache();
        }
    }

    protected static function saveToCache()
    {
        if(array_key_exists(static::class, self::$resolves)) {
            if(!isset(self::$resolves[static::class]['resolved'])) {
                return;
            }

            /**
             * @var CacheInterface $cache
             */
            $cache = Kernel::$container->get(CacheInterface::class);
            $cacheKey = 'entity-' . str_replace('\\', '_', static::class);

            $definition = self::$resolves[static::class];
            $definition['resolved'] = false;

            $cache->set($cacheKey, $definition);
        }
    }

    protected static function tryResolveFromRegistry(): bool
    {
        if(array_key_exists(static::class, self::$resolves)) {
            if(!isset(self::$resolves[static::class]['resolved'])) {
                static::$properties = self::$resolves[static::class]['properties'];
                static::$accessors = self::$resolves[static::class]['accessors'];
                static::$mutators = self::$resolves[static::class]['mutators'];

                self::$resolves[static::class]['resolved'] = true;
            }

            return true;
        }

        return false;
    }

    protected static function tryResolveFromCache(): bool
    {
        /**
         * @var CacheInterface $cache
         */
        $cache = Kernel::$container->get(CacheInterface::class);
        $cacheKey = 'entity-' . str_replace('\\', '_', static::class);
        if(is_null($cache) || !$cache->has($cacheKey)) {
            return false;
        }

        $definition = $cache->get($cacheKey);
        $definition['resolved'] = true;

        self::$resolves[static::class] = $definition;

        return true;
    }

    protected static function resolveAnnotations()
    {
        static::resolveProperty();
        $reader = new EntityReader(static::class);
        $reader->loadProperties(static::$properties);
        $reader->loadAccessors(static::$accessors);
        $reader->loadMutators(static::$mutators);

        self::$resolves[static::class] = [
            'properties' => static::$properties,
            'accessors' => static::$accessors,
            'mutators' => static::$mutators,
            'resolved' => true,
        ];
    }

    private static function resolveProperty()
    {
        if(is_array(static::$properties)) {
            $resolvedProperties = [];
            $isResolved = true;
            foreach(static::$properties as $name => $behavior) {
                if(is_numeric($name)) {
                    if(is_string($behavior)) {
                        $resolvedProperties[$behavior] = null;
                    }
                    else {
                        $isResolved = false;
                        break;
                    }
                }
                elseif(is_string($name)) {
                    if(is_array($behavior)) {
                        $resolved = [
                            $behavior[0] ?? $behavior['type'],
                            $behavior[1] ?? $behavior['nullable'] ?? false,
                        ];
                    }
                    elseif(is_string($behavior)) {
                        $resolved = [$behavior, false];
                    }
                    else {
                        $resolved = null;
                    }
                    
                    if(is_array($resolved) && (!is_string($resolved[0]) || !is_bool($resolved[1]))) {
                        $isResolved = false;
                        break;
                    }

                    $resolvedProperties[$name] = $resolved;
                }
                else {
                    $isResolved = false;
                    break;
                }
            }

            if(!$isResolved) {
                $class = static::class;
                throw new EntityException(
                    "Could not resolve property definition for class '$class'"
                );
            }

            static::$properties = $resolvedProperties;
        }
    }
}