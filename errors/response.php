<?php

if(!isset($code, $message)) {
    include '404.php';
}

http_response_code($code);
header('Content-Type: application/json');
header('Cache-Control: no-cache');
if(isset($_SERVER['ENV']) && $_SERVER['ENV'] == 'development') {
    header('X-Error-Source: htaccess');
}

echo json_encode(compact('code', 'message'));
