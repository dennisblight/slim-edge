<?php

declare(strict_types=1);

namespace SlimEdge\Annotation\Route;

use Doctrine\Common\Annotations\Annotation;
use SlimEdge\Annotation\Route;

/**
 * @Annotation
 * @Annotation\NamedArgumentConstructor
 * @Annotation\Target({"METHOD"})
 */
class Purge extends Route
{
    public function __construct(string $path, string $name = null, array $arguments = [])
    {
        parent::__construct(['PURGE'], $path, $name, $arguments);
    }
}