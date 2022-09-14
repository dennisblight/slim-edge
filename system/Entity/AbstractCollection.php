<?php

declare(strict_types=1);

namespace SlimEdge\Entity;

abstract class AbstractCollection extends \ArrayObject implements \JsonSerializable
{
    /**
     * @param iterable|object $data
     */
    public function __construct($data = [])
    {
        parent::__construct([], \ArrayObject::ARRAY_AS_PROPS | \ArrayObject::STD_PROP_LIST);
        
        if(is_scalar($data) || is_null($data)) {
            $data = [$data];
        }

        foreach($data as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    public function has($key): bool
    {
        return $this->offsetExists($key);
    }

    public function get($key, $default = null)
    {
        return $this->offsetExists($key) ? $this->offsetGet($key) : $default;
    }

    public function set($key, $value): void
    {
        $this->offsetSet($key, $value);
    }

    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    public function remove($key): void
    {
        $this->offsetUnset($key);
    }

    public function clear(): void
    {
        foreach($this->getArrayCopy() as $key => $value) {
            $this->offsetUnset($key);
        }
    }

    /**
     * @param iterable|object $data
     */
    public function replace($data): void
    {
        $this->clear();
        $this->merge($data);
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

    public function all(): array
    {
        return $this->getArrayCopy();
    }

    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}