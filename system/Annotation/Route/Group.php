<?php

declare(strict_types=1);

namespace SlimEdge\Annotation\Route;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Annotation\NamedArgumentConstructor
 * @Annotation\Target({"CLASS"})
 */
class Group
{
    public $path = null;

    public function __construct(string $path = null)
    {
        $this->path = $path;
    }
}