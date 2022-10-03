<?php

http_response_code(500);
header('Content-Type: application/json');
header('Cache-Control: no-cache');

echo json_encode([
    'code' => 500,
    'message' => 'Unexpected condition encountered preventing server from fulfilling request.'
]);
