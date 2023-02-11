<?php

declare(strict_types=1);

if(! function_exists('hashids'))
{
    /**
     * @param ?string $key
     * @return Hashids\Hashids
     */
    function hashids(?string $key = null)
    {
        return container($key ? "hashids.{$key}" : Hashids\Hashids::class);
    }
}

if(! function_exists('hashids_encode'))
{
    /**
     * @param int ...$numbers
     * @return string Encoded hashids
     */
    function hashids_encode(...$numbers): string
    {
        /** @var Hashids\Hashids $hashids */
        $hashids = container(Hashids\Hashids::class);

        return $hashids->encode(...$numbers);
    }
}

if(! function_exists('hashids_decode'))
{
    /**
     * @param string $hash
     * @return int[] Decoded hashids
     */
    function hashids_decode($hash): array
    {
        /** @var Hashids\Hashids $hashids */
        $hashids = container(Hashids\Hashids::class);

        return $hashids->decode($hash);
    }
}