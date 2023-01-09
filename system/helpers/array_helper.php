<?php

if (!function_exists('array_item')) {
    /**
     * @param array $array
     * @param mixed $key
     * @param mixed $default
     */
    function array_item($array, $key, $default = null)
    {
        return $array[$key] ?? $default;
    }
}

if (!function_exists('get_item')) {
    /**
     * @param mixed $object
     * @param mixed $key
     * @param mixed $default
     */
    function get_item($object, $key, $default = null)
    {
        return array_item((array) $object, $key, $default);
    }
}

if (!function_exists('array_map_with_key')) {
    function array_map_with_key($array, $key)
    {
        $result = [];
        foreach ($array as $item) {
            $itemArray = (array) $item;
            $result[$itemArray[$key]] = $item;
        }
        return $result;
    }
}

if (!function_exists('coalesce')) {
    /**
     * Null coalescing operator for the common case of needing to use
     * ternary conjuction with isset()
     * 
     * @param mixed $args,... 
     * @return mixed|null
     */
    function coalesce()
    {
        $args = func_get_args();
        foreach ($args as $item) {
            if (!is_null($item)) {
                return $item;
            }
        }
        return null;
    }
}

if (!function_exists('array_filter_use_both')) {
    /**
     * Iterates over each value in the array passing them to the callback function.
     * If the callback function returns true, the current value from array is returned into the result array. Array keys are preserved.
     * 
     * @param array $array — The array to iterate over
     * 
     * @param callback$callback
     * The callback function to use
     * 
     * If no callback is supplied, all entries of input equal to false (see converting to boolean) will be removed.
     */
    function array_filter_use_both($array, $callable)
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (call_user_func($callable, $key, $value)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}

if (!function_exists('array_is_list')) {
    function array_is_list(array $array)
    {
        for ($i = 0; $i < count($array); $i++) {
            if (!isset($array[$i])) return false;
        }
        return true;
    }
}

if (!function_exists('array_merge_deep')) {
    function array_merge_deep(array $array, array ...$arrays)
    {
        foreach($arrays as $array2) {
            foreach($array2 as $key => $value2) {
                if(array_key_exists($key, $array) && is_array($value2)) {
                    $value = $array[$key];
                    if(is_array($value)) {
                        $value2 = array_merge_deep($value, $value2);
                    }
                }

                $array[$key] = $value2;
            }
        }
        return $array;
    }
}