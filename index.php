<?php
define('BASEPATH', realpath(__DIR__));
define('ENVIRONMENT', $_SERVER['ENV'] ?? 'production');

require_once BASEPATH . '/vendor/autoload.php';

SlimEdge\Kernel::boot()->run();
// var_dump($_SERVER);