<?php

declare(strict_types=1);

use DI\Factory\RequestedEntry;
use Psr\Http\Message\ServerRequestInterface;
use SlimEdge\Entity\AbstractForm;
use SlimEdge\Paths;

$dependencies = [];

foreach(rglob(Paths::Entity . '/*.php') as $entityClassFile) {
    $className = substr($entityClassFile, strlen(Paths::Entity) + 1, -4);
    $entityClass = 'App\\Data\\' . $className;
    if(class_exists($entityClass) && is_subclass_of($entityClass, AbstractForm::class)) {
        $dependencies[$entityClass] = \DI\factory(function(RequestedEntry $entry, ServerRequestInterface $request) {
            return $entry->getName()::fromRequest($request);
        });
    }
}

return $dependencies;