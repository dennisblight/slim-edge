<?php

declare(strict_types=1);

namespace SlimEdge\Entity;

class Collection extends AbstractCollection
{
    /**
     * @var bool $recursiveCollection
     */
    protected $recursiveCollection = true;

    public function __construct($data = [], $recursiveCollection = true)
    {
        $this->recursiveCollection = $recursiveCollection;
        $this->replace($data);
    }
    
    public function offsetSet($key, $value): void
    {
        if($this->recursiveCollection && !($value instanceof \Closure) && (is_iterable($value) || is_object($value))) {
            if(!($value instanceof AbstractCollection)) {
                $value = new Collection($value, true);
            }
        }
        
        parent::offsetSet($key, $value);
    }

    public function exists($needle, bool $strict = false): bool
    {
        if($strict) foreach($this as $value) if($value === $needle) return true;
        else foreach($this as $value) if($value == $needle) return true;
        return false;
    }
}