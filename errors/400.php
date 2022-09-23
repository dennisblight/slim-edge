<?php

http_response_code(400);
header('Content-Type: application/json');
header('Cache-Control: no-cache');

echo json_encode([
    'status' => 400,
    'message' => 'The server cannot or will not process the request due to an apparent client error.'
]);
