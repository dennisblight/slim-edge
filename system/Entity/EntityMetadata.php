<?php
namespace SlimEdge\Entity;

class EntityMetadata
{
    /**
     * @var string $property
     */
    public $property;

    /**
     * @var bool $nullable
     */
    public $nullable;

    /**
     * @var string $type
     */
    public $type;

    /**
     * @var string $accessor
     */
    public $accessor;

    /**
     * @var string $mutator
     */
    public $mutator;

    /**
     * @var array $validator
     */
    public $validator;

    /**
     * @var mixed $default
     */
    public $default;
}