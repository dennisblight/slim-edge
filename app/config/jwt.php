<?php

return [
    'header'      => 'token',
    'algorithm'   => 'RS512',
    
    'public_key'  => function() {
        return file_get_contents(BASEPATH . '/resources/jwt-key/rsa.key.pub');
    },

    'private_key' => function() {
        return file_get_contents(BASEPATH . '/resources/jwt-key/rsa.key');
    },

    'duration'    => '3 hours',
];