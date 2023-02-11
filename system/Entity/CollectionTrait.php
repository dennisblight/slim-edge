<?php

declare(strict_types=1);

trait CollectionTrait
{
    public function has($key): bool
    {
        return $this->offsetExists($key);
    }

    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    public function get($key, $default = null)
    {
        if(func_num_args() === 1)
            return $this->offsetGet($key);

        return $this->offsetExists($key) ? $this->offsetGet($key) : $default;
    }

    public function set($key, $value): void
    {
        $this->offsetSet($key, $value);
    }

    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    public function remove($key): void
    {
        $this->offsetUnset($key);
    }
}