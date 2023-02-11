<?php

declare(strict_types=1);

if(!function_exists('typeof'))
{
    /**
     * @param mixed $value
     */
    function typeof($value): string
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}

if(!function_exists('var_compile'))
{
    function var_compile($value, $indentLevel = 0)
    {
        if(is_scalar($value) || is_null($value)) {
            return var_export($value, true);
        }
        elseif(is_array($value)) {
            $result = "[\n";
            $isList = array_is_list($value);
            foreach($value as $key => $item) {
                $result .= str_repeat('    ', $indentLevel + 1);
                if(!$isList) {
                    $result .= var_compile($key);
                    $result .= ' => ';
                }

                $result .= var_compile($item, $indentLevel + 1);
                $result .= ",\n";
            }
            return $result . str_repeat('    ', $indentLevel) . ']';
        }
        elseif($value instanceof SlimEdge\Entity\Collection) {
            return var_compile($value->getArrayCopy(), $indentLevel);
        }
        else {
            $type = typeof($value);
            throw new RuntimeException("Could not resolve '{$type}' parameter value from varExport");
        }
    }
}