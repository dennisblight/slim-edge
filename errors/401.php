<?php

http_response_code(401);
header('Content-Type: application/json');
header('Cache-Control: no-cache');

echo json_encode([
    'status' => 401,
    'message' => 'The request requires valid user authentication.'
]);
