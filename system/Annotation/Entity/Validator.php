<?php

declare(strict_types=1);

namespace SlimEdge\Annotation\Entity;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("METHOD")
 */
class Validator
{
    /**
     * @var string $property
     */
    private $property;

    public function getProperty(): string
    {
        return $this->property;
    }

    public function __construct(string $property)
    {
        $this->property = $property;
    }
}