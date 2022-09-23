<?php

declare(strict_types=1);

namespace SlimEdge\Annotation\Route;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
use InvalidArgumentException;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Middleware
{
    public $middlewares = [];

    public function __construct(...$values)
    {
        $middlewares = array_map(function($item) {
            $value = $item['value'];
            $baseName = $value;
            $valid = class_exists($value)
                || class_exists($value = 'SlimEdge\\Middleware\\' . $baseName)
                || class_exists($value = $value . 'Middleware')
                || class_exists($value = 'App\\Middleware\\' . $baseName)
                || class_exists($value = $value . 'Middleware')
            ;

            if(!$valid) throw new InvalidArgumentException("Could not resolve '$baseName' middleware");

            return $value;
        }, $values);

        $this->middlewares = array_filter($middlewares, function($item) {
            return !empty($item);
        });
    }
}
