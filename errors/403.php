<?php

http_response_code(403);
header('Content-Type: application/json');
header('Cache-Control: no-cache');

echo json_encode([
    'status' => 403,
    'message' => 'You are not permitted to perform the requested operation.'
]);
