<?php

return [
    'db'   => DI\get(Database::class),
    'db.*' => DI\factory(function(DI\Factory\RequestedEntry $entry, SlimEdge\Libraries\Database $db) {
        $dbKey = explode('.', $entry->getName())[1];
        return $db->connection($dbKey);
    }),
];