<?php

http_response_code(501);
header('Content-Type: application/json');
header('Cache-Control: no-cache');

echo json_encode([
    'code' => 501,
    'message' => 'The server does not support the functionality required to fulfill the request.'
]);
