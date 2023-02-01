<?php

if(!function_exists('normalize_path'))
{
    function normalize_path(string $path, $trimSeparator = true): string
    {
        $path = str_replace(
            ['/', '\\'],
            [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
            $path
        );

        $slashSlash = DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR;
        while (strpos($path, $slashSlash) !== false)
        {
            $path = str_replace($slashSlash, DIRECTORY_SEPARATOR, $path);
        }

        return $trimSeparator ? rtrim($path, DIRECTORY_SEPARATOR) : $path;
    }
}

if(!function_exists('join_path'))
{
    function join_path(string ...$paths): string
    {
        $paths = array_map(function($path) {
            if($path == '/' || $path == '\\') return $path;
            while(str_starts_with($path, ' ') || str_starts_with($path, '/') || str_starts_with($path, '\\'))
            {
                $path = ltrim($path, ' /\\');
            }
            while(str_ends_with($path, ' ') || str_ends_with($path, '/') || str_ends_with($path, '\\'))
            {
                $path = rtrim($path, ' /\\');
            }
            return $path;
        }, $paths);

        return normalize_path(join(DIRECTORY_SEPARATOR, $paths));
    }
}

if(!function_exists('rglob'))
{
    function rglob(string $pattern, int $flags = 0, $filter = null): array {
        $files = glob($pattern, $flags);

        if(!is_null($filter))
            $files = array_filter($files, $filter);

        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $_files = rglob($dir.'/'.basename($pattern), $flags, $filter);

            if(!is_null($filter))
                $_files = array_filter($_files, $filter);

            $files = array_merge($files, $_files);
        }
        return $files;
    }
}