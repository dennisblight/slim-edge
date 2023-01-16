<?php

use DI\Factory\RequestedEntry;
use SlimEdge\Libraries\Database;
use SqlTark\XQuery;

use function SlimEdge\Helpers\load_config;

$dependencies = [
    'db' => DI\get(Database::class),
    XQuery::class => DI\get(Database::class),
];

$dbConfig = load_config('database');
foreach($dbConfig['connections'] as $key => $connection) {
    $dependencies['db.' . $key] = DI\factory(function(RequestedEntry $entry, Database $db) {
        $dbKey = explode('.', $entry->getName())[ 1 ];
        return $db->connection($dbKey);
    });
}

return $dependencies;