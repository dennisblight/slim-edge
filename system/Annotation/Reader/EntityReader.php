<?php

declare(strict_types=1);

namespace SlimEdge\Annotation\Reader;

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use SlimEdge\Annotation;
use SlimEdge\Annotation\Entity\Accessor;
use SlimEdge\Annotation\Entity\Mutator;
use SlimEdge\Entity\EntityMetadata;
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

    /**
     * @var PhpDocReader $docReader
     */
    private $docReader;

    public function __construct(string $className)
    {
        $this->reflection = new ReflectionClass($className);
        $this->docReader = new PhpDocReader;
    }

    public function readMetadata()
    {
        $properties = array_merge_deep(
            $this->docReader->readClassProperties($this->reflection),
            // $this->docReader->readProperties($this->reflection),
        );

        $reader = $this->getAnnotationReader();

        foreach($this->getMethods() as $name => $method) {

            /** @var ?Annotation\Entity\Accessor $accessor */
            $accessor = $reader->getMethodAnnotation($method, Annotation\Entity\Accessor::class);

            /** @var ?Annotation\Entity\Mutator $mutator */
            $mutator = $reader->getMethodAnnotation($method, Annotation\Entity\Mutator::class);

            /** @var ?Annotation\Entity\Validator $validator */
            $validator = $reader->getMethodAnnotation($method, Annotation\Entity\Validator::class);

            if(!is_null($accessor)) {
                $property = $accessor->getProperty();
                $properties[$property] = array_merge([
                    'property' => $property,
                    'accessor' => $name,
                ], $properties[$property] ?? []);
            }

            if(!is_null($mutator)) {
                $property = $mutator->getProperty();
                $properties[$property] = array_merge([
                    'property' => $property,
                    'mutator' => $name,
                ], $properties[$property] ?? []);
            }

            if(!is_null($validator)) {
                $property = $validator->getProperty();
                $properties[$property] = array_merge([
                    'property' => $property,
                    'validator' => $name,
                ], $properties[$property] ?? []);
            }
        }

        $metadata = [];
        foreach($properties as $key => $prop) {
            $m = new EntityMetadata;
            $m->property  = $prop['property'] ?? $key;
            $m->type      = $prop['type'] ?? 'mixed';
            $m->nullable  = $prop['nullable'] ?? true;
            $m->accessor  = $prop['accessor'] ?? null;
            $m->mutator   = $prop['mutator'] ?? null;
            $m->validator = $prop['validator'] ?? null;
            $m->default   = $prop['default'] ?? null;
            $metadata[$key] = $m;
        }

        return $metadata;
    }

    /**
     * @return ReflectionMethod[]
     */
    public function getMethods(): array
    {
        if(!isset($this->methods)) {
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
     * @return AnnotationReader
     */
    public function getAnnotationReader()
    {
        return container(AnnotationReader::class);
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
            /** @var ?Annotation\Entity\Property $property */
            $property = $reader->getPropertyAnnotation($prop, Annotation\Entity\Property::class);
            if(!is_null($property)) {
                // $type = $property->getType();
                // $array[$name] = [$type, $property->isNullable()];
            }
        }
    }
}
