<?php

return [
    /**
     * Maximum log file size
     */
    'maxFileSize' => 10000000,

    /**
     * Log base path
     */
    'path' => BASEPATH . '/storage/logs/http',

    /**
     * Log writer class
     */
    'writer' => SlimEdge\HttpLog\Writer\FileWriter::class,

    'logRequest' => [
        /**
         * Maximum size of body should write to log file
         */
        'maxBody' => 2000,

        /**
         * Ignore body when body size reached maximum
         * or write to new file when not ignored
         */
        'ignoreOnMax' => false,

        /**
         * Ignore logging when the request method in specified value
         */
        'ignoreMethods' => ['OPTION'],

        /**
         * Only log specified headers
         */
        'headers' => [
            'Accept',
            'Authorization',
            'Content-Length',
            'Content-Type',
            'Host',
            'Origin',
            'User-Agent',
        ],

        /**
         * Ignore logging when the request route name is in specified value
         */
        'ignoreRoutes' => ['readLog', 'index'],
    ],

    'logResponse' => [
        /**
         * Maximum size of body should write to log file
         */
        'maxBody' => 2000,

        /**
         * Ignore body when body size reached maximum
         * or write to new file when not ignored
         */
        'ignoreOnMax' => false,

        /**
         * Ignore logging when status code is in specified value
         */
        'ignoreStatusCodes' => ['1xx', '3xx'],

        /**
         * Only log specified headers
         */
        'headers' => [
            'Access-Control-Allow-Origin',
            'Access-Control-Allow-Credentials',
            'Access-Control-Expose-Headers',
            'Access-Control-Max-Age',
            'Access-Control-Allow-Methods',
            'Access-Control-Allow-Headers',
            'Access-Range',
            'Allow',
            'Content-Disposition',
            'Content-Length',
            'Content-Range',
            'Content-Type',
            'Location',
            'WWW-Authenticate',
        ],

        /**
         * Ignore logging when the request route name is in specified value
         */
        'ignoreRoutes' => ['readLog', 'index'],
    ],

    'routes' => [
        'index' => [
        ]
    ],
];