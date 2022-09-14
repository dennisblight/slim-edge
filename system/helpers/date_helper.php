<?php

if(!function_exists('next_month'))
{
    function next_month($date = null): string
    {
        $date = is_null($date) ? date('Y-m-d') : date($date);
        $dateObject = new DateTime($date);
        
        return date_add($dateObject, new DateInterval('P1M'))->format('Y-m-d');
    }
}

if (!function_exists('previous_month'))
{
    function previous_month($date = null): string
    {
        $date = is_null($date) ? date('Y-m-d') : date($date);
        $dateObject = new DateTime($date);

        return date_sub($dateObject, new DateInterval('P1M'))->format('Y-m-d');
    }
}

if (!function_exists('previous_period'))
{
    function previous_period($period = null): string
    {
        $date = is_null($period) ? date_create() : date_create_from_format('Ym', $period);
        return date_sub($date, new DateInterval('P1M'))->format('Ym');
    }
}

if (!function_exists('next_period'))
{
    function next_period($period = null): string
    {
        $date = is_null($period) ? date_create() : date_create_from_format('Ym', $period);
        return date_add($date, new DateInterval('P1M'))->format('Ym');
    }
}