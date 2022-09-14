<?php

if (!function_exists('is_https')) {
    function is_https()
    {
        if (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] === 'on') {
            return true;
        }

        if (array_key_exists("SERVER_PORT", $_SERVER) && 443 === (int) $_SERVER["SERVER_PORT"]) {
            return true;
        }

        if (array_key_exists("HTTP_X_FORWARDED_SSL", $_SERVER) && 'on' === $_SERVER["HTTP_X_FORWARDED_SSL"]) {
            return true;
        }

        if (array_key_exists("HTTP_X_FORWARDED_PROTO", $_SERVER) && 'https' === $_SERVER["HTTP_X_FORWARDED_PROTO"]) {
            return true;
        }

        return false;
    }
}

if (!function_exists('get_base_path')) {
    function get_base_path(): string
    {
        return str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
    }
}

if (!function_exists('get_base_url')) {
    function get_base_url(): string
    {
        static $baseURL = null;
        if (is_null($baseURL)) {
            $baseURL = is_https() ? 'https' : 'http';
            $baseURL .= '://' . $_SERVER['SERVER_NAME'];
            $baseURL .= get_base_path() . '/';
        }

        return $baseURL;
    }
}

if (!function_exists('url')) {
    function url($path = ''): string
    {
        $url = get_base_url() . '/' . $path;
        while (strpos($url, '//') !== false) {
            $url = str_replace('//', '/', $url);
        }
        return $url;
    }
}
