<?php

declare(strict_types=1);

namespace SlimEdge\Entity;

use Psr\SimpleCache\CacheInterface;
use Respect\Validation\Validator;
use Respect\Validation\Rules;
use SlimEdge\Annotation\Reader\EntityReader;
use SlimEdge\Annotation\Reader\EntityReader2;
use SlimEdge\Exceptions\EntityException;
use SlimEdge\Helpers;
use SlimEdge\Kernel;

use function SlimEdge\Helpers\enable_cache;

class AbstractEntity extends AbstractCollection
{
    /**
     * @var bool $resolved
     */
    protected static $resolved = false;

    /**
     * @var bool $expandable
     * Allow entity to add property
     */
    protected static $expandable = false;

    /**
     * @var array<string, EntityMetadata> $metadata
     */
    protected static $metadata = [];

    /**
     * @var Validator? $validator
     */
    protected static $validator = null;

    /**
     * @param iterable|object $data
     */
    public function __construct($data = [])
    {
        static::resolveBehavior();

        foreach(array_keys(static::$metadata) as $key) {
            if(!isset($data[$key])) {
                $data[$key] = $this->getDefault($key);
            }
        }

        parent::__construct($data);
    }

    public function offsetGet($key)
    {
        // Try get from accessor
        if($this->access($key, $result)) {
            return $result;
        }

        // Try get from current collection if exists
        if(parent::offsetExists($key)) {
            return parent::offsetGet($key);
        }

        // Try get default from metadata
        if(array_key_exists($key, static::$metadata)) {
            return $this->getDefault($key);
        }

        // Forbid to access non-exists member for non-expandable class
        if(!static::$expandable) {
            throw new \OutOfBoundsException("Undefined offset: '{$key}'");
        }

        return null;
    }

    public function offsetSet($key, $value): void
    {
        if(!array_key_exists($key, static::$metadata) && !static::$expandable) {
            return;
        }

        // Try to cast
        if(array_key_exists($key, static::$metadata)) {
            $metadata = static::$metadata[$key];
            $value = $this->cast($metadata->type, $value, $metadata->nullable);
        }

        // Try set using mutator
        if($this->mutate($key, $value, $mutated)) {
            $value = $mutated;
        }
        
        parent::offsetSet($key, $value);
    }

    protected function access($key, &$result): bool
    {
        if(!array_key_exists($key, static::$metadata) || is_null($accessor = static::$metadata[$key]->accessor)) {
            // Accessor not set
            return false;
        }

        elseif(method_exists($this, $accessor)) {
            // Accessor available as method
            $result = call_user_func([$this, $accessor], $key);
            return true;
        }

        elseif(is_callable($accessor)) {
            // Accessor available as function
            $result = call_user_func($accessor, $key);
            return true;
        }

        // Accessor available, but not callable
        throw new EntityException(
            "Could not resolve accessor '{$accessor}'"
        );
    }

    protected function mutate($key, $value, &$result): bool
    {
        if(!array_key_exists($key, static::$metadata)) {
            // Mutator not available, metadata not found
            return false;
        }

        $mutator = static::$metadata[$key]->mutator;

        if(is_null($mutator)) {
            // Mutator not set
            return false;
        }

        elseif(method_exists($this, $mutator)) {
            // Mutator available as method
            $result = call_user_func([$this, $mutator], $key, $value);
            return true;
        }

        elseif(is_callable($mutator)) {
            // Mutator available as method
            $result = call_user_func($mutator, $key, $value);
            return true;
        }

        throw new EntityException(
            "Could not resolve mutator type '{$mutator}'"
        );
    }

    protected function cast($type, $value, $nullable = false)
    {
        if($type == 'mixed' || ($nullable && is_null($value))) {
            return $value;
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
            case \DateTime::class:
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
            "Could not resolve cast type '{$type}'"
        );
    }

    protected function getValidator($key)
    {
        if(!array_key_exists($key, static::$metadata))
            return null;

        $validator = static::$metadata[$key]->validator;
        if(empty($validator))
            return null;

        return call_user_func([$this, $validator], $key);
    }

    public function getValidators()
    {
        if(!empty(static::$validator))
            return static::$validator;

        $rules = [];
        foreach(static::$metadata as $metadata) {
            if(!empty($metadata->validator)) {
                [$validatorMethodName, $isMandatory] = $metadata->validator;
                $validator = call_user_func([$this, $validatorMethodName], $metadata->property);
                $rule = new Rules\Key($metadata->property, $validator, $isMandatory);
                array_push($rules, $rule);
            }
        }

        return static::$validator = new Rules\KeySet(...$rules);
    }

    protected function getDefault($key)
    {
        $metadata = static::$metadata[$key];
        if($metadata->type == 'mixed' || $metadata->nullable) return null;

        switch($metadata->type) {
            case 'bool':
            case 'boolean':
                return false;
            case 'string':
                return '';
            case 'int':
            case 'integer':
            case 'float':
            case 'real':
            case 'double':
                return 0;
            case 'date':
                return Helpers\cast_date(date('Y-m-d H:i:s'));
            case 'time':
                return Helpers\cast_time(date('Y-m-d H:i:s'));
            case 'datetime':
                return Helpers\cast_datetime(date('Y-m-d H:i:s'));
            case 'array':
                return [];
            case 'object':
                return new \stdClass;
            case 'collection':
                return new Collection();
        }

        if(class_exists($metadata->type) && is_subclass_of($metadata->type, AbstractEntity::class)) {
            $type = $metadata->type;
            return new $type();
        }

        throw new EntityException(
            "Could not resolve default type '{$metadata->type}'"
        );
    }

    public static function resolveBehavior()
    {
        if(!static::$resolved && !static::tryResolveFromCache()) {
            static::resolve();
            static::saveToCache();
        }
    }

    protected static function resolve()
    {
        if(!static::$resolved) {
            $reader = new EntityReader2(static::class);
            static::$metadata = $reader->readMetadata();
            static::$resolved = true;
            return true;
        }

        return false;
    }

    protected static function saveToCache()
    {
        if(!enable_cache('entity')) {
            return false;
        }

        /** @var CacheInterface $cache */
        $cache = Kernel::$container->get(CacheInterface::class);
        $cacheKey = 'entity-' . str_replace('\\', '_', static::class);
        $cache->set($cacheKey, static::$metadata);
        return true;
    }

    protected static function tryResolveFromCache(): bool
    {
        if(!enable_cache('entity')) {
            return false;
        }

        /** @var CacheInterface $cache */
        $cache = Kernel::$container->get(CacheInterface::class);
        $cacheKey = 'entity-' . str_replace('\\', '_', static::class);
        if(is_null($cache) || !$cache->has($cacheKey)) {
            return false;
        }

        static::$metadata = $cache->get($cacheKey);

        return static::$resolved = true;
    }
}