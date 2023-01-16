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
     * @var \Closure $accessor
     */
    public $accessor;

    /**
     * @var \Closure $mutator
     */
    public $mutator;

    // public function __construct(string $property, bool $nullable, string $type, \Closure $accessor, \Closure $mutator)
    // {
    //     $this->property = $property;
    //     $this->nullable = $nullable;
    //     $this->type = $type;
    //     $this->accessor = $accessor;
    //     $this->mutator = $mutator;
    // }

    // /**
    //  * @return string
    //  */
    // public function getProperty()
    // {
    //     return $this->property;
    // }

    // /**
    //  * @return bool
    //  */
    // public function isNullable()
    // {
    //     return $this->nullable;
    // }

    // /**
    //  * @return string
    //  */
    // public function getType()
    // {
    //     return $this->type;
    // }

    // /**
    //  * @return \Closure
    //  */
    // public function getAccessor()
    // {
    //     return $this->accessor;
    // }

    // /**
    //  * @return \Closure
    //  */
    // public function getMutator()
    // {
    //     return $this->mutator;
    // }
}