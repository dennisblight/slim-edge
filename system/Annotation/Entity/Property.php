<?php

declare(strict_types=1);

namespace SlimEdge\Annotation\Entity;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("PROPERTY")
 */
class Property
{
    /**
     * @var string $type
     */
    private $type;
    
    /**
     * @var bool $nullable
     */
    private $nullable;

    public function getType(): string
    {
        return $this->type;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function __construct(string $type = 'string', bool $nullable = false)
    {
        $this->type = $type;
        $this->nullable = $nullable;
    }
}