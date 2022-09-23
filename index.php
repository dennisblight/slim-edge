<?php
define('BASEPATH', realpath(__DIR__));
define('ENVIRONMENT', $_SERVER['ENV'] ?? 'production');

require_once BASEPATH . '/vendor/autoload.php';

// SlimEdge\Kernel::boot()->run();

// header('Content-Type: text/plain');
// var_dump($_SERVER);
// header