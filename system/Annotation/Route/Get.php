<?php

declare(strict_types=1);

namespace SlimEdge\Annotation\Route;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use SlimEdge\Annotation\Route;

/**
 * @Annotation
 * @Annotation\NamedArgumentConstructor
 * @Annotation\Target({"METHOD"})
 */
class Get extends Route
{
    public function __construct(string $path, string $name = null, array $arguments = [])
    {
        parent::__construct(['GET'], $path, $name, $arguments);
    }
}