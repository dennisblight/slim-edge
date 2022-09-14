<?php

use Core\Middleware\Logger\Writer\FileWriter;

return [
    'max_length'     => 0xFFFF,
    
    'writer'         => FileWriter::class,
    'path'           => BASEPATH . '/storage/logs/http',
    'max_file_size'  => 10e6,
    'ignore_file'    => false,
    'ignore_headers' => [
        'accept_encoding',
        'dnt',
        'connection',
        'upgrade_insecure_requests',
        'sec_fetch_dest',
        'sec_fetch_mode',
        'sec_fetch_site',
        'sec_fetch_user',
        'cache_control',
    ],
];