<?php

namespace SlimEdge\Helpers;

if(! function_exists('SlimEdge\Helpers\cast_integer'))
{
    function cast_integer($value): int
    {
        if(is_numeric($value) || is_bool($value) || is_null($value)) {
            return intval($value);
        }

        if(is_string($value)) {
            throw new \SlimEdge\Exceptions\CastException("Could not cast string from '$value' to integer");
        }

        $type = is_object($value) ? get_class($value) : gettype($value);
        throw new \SlimEdge\Exceptions\CastException("Could not cast from type '$type' to integer");
    }
}

if(! function_exists('SlimEdge\Helpers\cast_float'))
{
    function cast_float($value): float
    {
        if(is_numeric($value) || is_bool($value)) {
            return floatval($value);
        }

        if(is_string($value)) {
            throw new \SlimEdge\Exceptions\CastException("Could not cast string from '$value' to float");
        }

        $type = is_object($value) ? get_class($value) : gettype($value);
        throw new \SlimEdge\Exceptions\CastException("Could not cast from type '$type' to float");
    }
}

if(! function_exists('SlimEdge\Helpers\cast_string'))
{
    function cast_string($value): string
    {
        if(is_scalar($value) || is_null($value) || (is_object($value) && method_exists($value, '__toString'))) {
            return strval($value);
        }

        $type = is_object($value) ? get_class($value) : gettype($value);
        throw new \SlimEdge\Exceptions\CastException("Could not cast from type '$type' to string");
    }
}

if(! function_exists('SlimEdge\Helpers\cast_boolean'))
{
    function cast_boolean($value): bool
    {
        if(is_scalar($value) || is_null($value)) {
            return boolval($value);
        }

        $type = is_object($value) ? get_class($value) : gettype($value);
        throw new \SlimEdge\Exceptions\CastException("Could not cast from type '$type' to boolean");
    }
}

if(! function_exists('SlimEdge\Helpers\cast_date'))
{
    function cast_date($value): \DateTime
    {
        if($value instanceof \DateTime) {
            return $value->setTime(0, 0, 0, 0);
        }

        if($dateObject = date_create($value)) {
            return $dateObject->setTime(0, 0, 0, 0);
        }

        if(is_string($value)) {
            throw new \SlimEdge\Exceptions\CastException("Could not cast string from '$value' to date");
        }

        $type = is_object($value) ? get_class($value) : gettype($value);
        throw new \SlimEdge\Exceptions\CastException("Could not cast from type '$type' to date");
    }
}

if(! function_exists('SlimEdge\Helpers\cast_time'))
{
    function cast_time($value): \DateTime
    {
        if($value instanceof \DateTime) {
            return $value->setDate(0, 1, 1);
        }

        if($dateObject = date_create($value)) {
            return $dateObject->setDate(0, 1, 1);
        }

        if(is_string($value)) {
            throw new \SlimEdge\Exceptions\CastException("Could not cast string from '$value' to time");
        }

        $type = is_object($value) ? get_class($value) : gettype($value);
        throw new \SlimEdge\Exceptions\CastException("Could not cast from type '$type' to time");
    }
}

if(! function_exists('SlimEdge\Helpers\cast_datetime'))
{
    function cast_datetime($value): \DateTime
    {
        if($value instanceof \DateTime) {
            return $value;
        }

        if($dateObject = date_create($value)) {
            return $dateObject;
        }

        if(is_string($value)) {
            throw new \SlimEdge\Exceptions\CastException("Could not cast string from '$value' to datetime");
        }

        $type = is_object($value) ? get_class($value) : gettype($value);
        throw new \SlimEdge\Exceptions\CastException("Could not cast from type '$type' to datetime");
    }
}