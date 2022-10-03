<?php

http_response_code(410);
header('Content-Type: application/json');
header('Cache-Control: no-cache');

echo json_encode([
    'code' => 410,
    'message' => 'The target resource is no longer available at the origin server.'
]);
