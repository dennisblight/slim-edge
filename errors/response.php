<?php

if(!isset($code, $message)) {
    include '404.php';
}

http_response_code($code);
header('Content-Type: application/json');
header('Cache-Control: no-cache');

echo json_encode(compact('code', 'message'));
