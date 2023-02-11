<?php

declare(strict_types=1);

if(! function_exists('bytes_format'))
{
    function bytes_format($num, $precision = 1)
	{
		if ($num >= 1000000000000)
		{
			$num = round($num / 1099511627776, $precision);
			$unit = 'TB';
		}
		elseif ($num >= 1000000000)
		{
			$num = round($num / 1073741824, $precision);
			$unit = 'GB';
		}
		elseif ($num >= 1000000)
		{
			$num = round($num / 1048576, $precision);
			$unit = 'MB';
		}
		elseif ($num >= 1000)
		{
			$num = round($num / 1024, $precision);
			$unit = 'KB';
		}
		else
		{
			$unit = 'B';
			return number_format($num).' '.$unit;
		}

		return number_format($num, $precision).' '.$unit;
	}
}

if(! function_exists('to_file_size'))
{
    function to_file_size(int $value, string $unit = 'bytes')
    {
        $unit = strtolower(trim($unit));
        switch($unit) {
            case 'tb': $value *= 1024;
            case 'gb': $value *= 1024;
            case 'mb': $value *= 1024;
            case 'kb': $value *= 1024;
        }
        return $value;
    }
}