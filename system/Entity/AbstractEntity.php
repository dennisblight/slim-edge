<?php

declare(strict_types=1);

namespace SlimEdge\Entity;

use ArrayIterator;
use Respect\Validation\Rules;
use Respect\Validation\Rules\AbstractRule;
use SlimEdge\Annotation\Reader\EntityReader;
use SlimEdge\Exceptions\CastException;
use SlimEdge\Exceptions\EntityException;
use SlimEdge\Helpers;
use Traversable;

use function SlimEdge\Helpers\get_cache;
use function SlimEdge\Helpers\set_cache;

abstract class AbstractEntity implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable, \JsonSerializable
{
    /**
     * @var bool $expandable
     * Allow entity to add property
     */
    protected static $expandable = false;

    /**
     * @var array<string, EntityMetadata>[] $metadata
     */
    protected static $metadata = [];

    /**
     * @var Validator? $validator
     */
    protected static $validator = null;

    protected $data = [];

    function __construct($data = [], $rawMode = false)
    {
        if($rawMode) {
            foreach($data as $key => $value) {
                $this->data[$key] = $value;
            }
        }
        else {
            foreach($data as $key => $value) {
                $this->offsetSet($key, $value);
            }
        }
    }

    protected static function getMetadata() {
        if(!array_key_exists(static::class, static::$metadata)) {
            static::resolveBehavior();
        }

        return static::$metadata[static::class];
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    public function __serialize(): array
    {
        return $this->data;
    }

    public function serialize()
    {
        return serialize($this->data);
    }

    public function __unserialize(array $data): void
    {
        $this->replace($data);
    }

    public function unserialize($data)
    {
        $this->replace($data);
    }

    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    public function offsetGet($key)
    {
        // Try get from accessor
        if($this->access($key, $result)) {
            return $result;
        }

        // Try get from current collection if exists
        if(array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        // Try get default from metadata
        $metadata = static::getMetadata();
        if(array_key_exists($key, $metadata)) {
            return $this->getDefault($key);
        }

        // Forbid to access non-exists member for non-expandable class
        if(!static::$expandable) {
            throw new \OutOfBoundsException("Undefined offset: '{$key}'");
        }

        return null;
    }

    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    public function offsetSet($key, $value): void
    {
        $meta = static::getMetadata();
        if($meta && (!array_key_exists($key, $meta) && !static::$expandable)) {
            return;
        }

        // Try to cast
        $exception = null;
        try
        {
            if(array_key_exists($key, $meta)) {
                $metadata = $meta[$key];
                $value = $this->cast($metadata->type, $value, $metadata->nullable);
            }
        }
        catch(CastException $ex)
        {
            $exception = $ex;
        }

        // Try set using mutator
        if($isMutated = $this->mutate($key, $value, $mutated)) {
            $value = $mutated;
        }

        if(!$isMutated && $exception) {
            throw $exception;
        }

        $this->data[$key] = $value;
    }

    public function offsetExists($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    public function offsetUnset($key): void
    {
        unset($this->data[$key]);
    }

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function all(): array
    {
        return $this->data;
    }

    /**
     * @param iterable|object $data
     */
    public function replace($data): void
    {
        $this->clear();
        $this->merge($data);
    }

    public function clear(): void
    {
        foreach(array_keys($this->data) as $key) {
            $this->offsetUnset($key);
        }
    }

    /**
     * @param iterable|object $data
     */
    public function merge($data): void
    {
        foreach ($data as $key => $value)
        {
            $this->offsetSet($key, $value);
        }
    }

    protected function access($key, &$result): bool
    {
        $meta = static::getMetadata();
        if(!array_key_exists($key, $meta) || is_null($accessor = $meta[$key]->accessor)) {
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
        $meta = static::getMetadata();
        if(!array_key_exists($key, $meta)) {
            // Mutator not available, metadata not found
            return false;
        }

        $mutator = $meta[$key]->mutator;

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

    protected function getValidator($key): AbstractRule
    {
        $meta = static::getMetadata();
        if(!array_key_exists($key, $meta))
            return null;

        $validator = $meta[$key]->validator;
        return is_null($validator) ? call_user_func([$this, $validator], $key) : null;
    }

    public function getValidators(): AbstractRule
    {
        if(isset(static::$validator[static::class]))
            return static::$validator[static::class];

        $rules = [];
        foreach(static::getMetadata() as $metadata) {
            if(!is_null($metadata->validator)) {
                [$validatorMethodName, $isMandatory] = $metadata->validator;
                $validator = call_user_func([$this, $validatorMethodName], $metadata->property);
                $rule = new Rules\Key($metadata->property, $validator, $isMandatory);
                array_push($rules, $rule);
            }
        }

        return static::$validator[static::class] = new Rules\KeySet(...$rules);
    }

    protected function getDefault($key)
    {
        $meta = static::getMetadata();
        $metadata = $meta[$key];
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

    protected static function resolveBehavior()
    {
        if(!static::tryResolveFromCache()) {
            static::resolve();
            static::saveToCache();
        }
    }

    protected static function resolve()
    {
        if(!array_key_exists(static::class, static::$metadata)) {
            $reader = new EntityReader(static::class);
            static::$metadata[static::class] = $reader->readMetadata();
            return true;
        }

        return false;
    }

    protected static function saveToCache(): bool
    {
        return set_cache(static::class, static::$metadata[static::class], 'entity');
    }

    protected static function tryResolveFromCache(): bool
    {
        $cached = get_cache(static::class, null, 'entity');
        if(is_null($cached)) {
            return false;
        }

        static::$metadata[static::class] = $cached;
        return true;
    }
}