<?php

http_response_code(404);
header('Content-Type: application/json');
header('Cache-Control: no-cache');

echo json_encode([
    'code' => 404,
    'message' => 'The requested resource could not be found. Please verify the URI and try again.'
]);
