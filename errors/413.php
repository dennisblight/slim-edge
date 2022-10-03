<?php

http_response_code(413);
header('Content-Type: application/json');
header('Cache-Control: no-cache');

echo json_encode([
    'code' => 413,
    'message' => 'The amount of data provided in the request exceeds the capacity limit.'
]);
