<?php

declare(strict_types=1);

namespace SlimEdge\Annotation\Reader;

use Doctrine\Common\Annotations\Reader;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use SlimEdge\Annotation\Entity\Accessor;
use SlimEdge\Annotation\Entity\Mutator;
use SlimEdge\Annotation\Entity\Property;
use SlimEdge\Kernel;

class EntityReader
{
    /**
     * @var ReflectionClass $reflection
     */
    private $reflection;

    /**
     * @var ?ReflectionMethod[] $methods
     */
    private $methods;

    /**
     * @var ?ReflectionProperty[] $properties
     */
    private $properties;

    public function __construct(string $className)
    {
        $this->reflection = new ReflectionClass($className);
    }

    /**
     * @return ReflectionMethod[]
     */
    public function getMethods(): array
    {
        if(!isset($this->methods))
        {
            $reflection = $this->reflection;
            $this->methods = [];
            foreach($reflection->getMethods() as $method) {
                if(!$method->isStatic()) {
                    $this->methods[$method->getName()] = $method;
                }
            }
        }

        return $this->methods;
    }

    /**
     * @return ReflectionProperty[]
     */
    public function getProperties(): array
    {
        if(!isset($this->properties))
        {
            $reflection = $this->reflection;
            $this->properties = [];
            foreach($reflection->getProperties() as $property) {
                if(!$property->isPublic() && !$property->isStatic()) {
                    $name = $property->getName();
                    $this->properties[$name] = $property;
                }
            }
        }

        return $this->properties;
    }

    /**
     * @return Reader
     */
    public function getAnnotationReader()
    {
        return Kernel::$container->get(Reader::class);
    }

    public function loadAccessors(?array &$array)
    {
        $reader = $this->getAnnotationReader();
        foreach($this->getMethods() as $name => $method) {
            if($method->getNumberOfParameters() === 1) {
                /** @var ?Accessor $accessor */
                $accessor = $reader->getMethodAnnotation($method, Accessor::class);
                if(!is_null($accessor)) {
                    $property = $accessor->getProperty();
                    $array[$property] = $name;
                }
            }
        }
    }

    public function loadMutators(?array &$array)
    {
        $reader = $this->getAnnotationReader();
        foreach($this->getMethods() as $name => $method) {
            if($method->getNumberOfParameters() === 1) {
                /** @var ?Mutator $mutator */
                $mutator = $reader->getMethodAnnotation($method, Mutator::class);
                if(!is_null($mutator)) {
                    $property = $mutator->getProperty();
                    $array[$property] = $name;
                }
            }
        }
    }

    public function loadProperties(?array &$array)
    {
        $reader = $this->getAnnotationReader();
        foreach($this->getProperties() as $name => $prop) {
            /** @var ?Property $property */
            $property = $reader->getPropertyAnnotation($prop, Property::class);
            if(!is_null($property)) {
                $type = $property->getType();
                $array[$name] = [$type, $property->isNullable()];
            }
        }
    }
}
