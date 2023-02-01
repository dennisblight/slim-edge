<?php

if(!function_exists('route_class'))
{
    function route_class($class, $method)
    {
        return $class . ':' . $method;
    }
}

if(!function_exists('timestamp'))
{
    function timestamp($time = 'now')
    {
        return date('Y-m-d H:i:s', strtotime($time));
    }
}

if(!function_exists('random_string'))
{
    function random_string($length = 10, $pool = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890-_')
    {
        $str = '';
        while(strlen($str) < $length)
        {
            $str .= $pool[random_int(0, strlen($str))];
        }
        return $str;
    }
}

if(!function_exists('str_pad_left'))
{
    function str_pad_left($string, $length, $padString = ' ')
    {
        return str_pad($string, $length, $padString, STR_PAD_LEFT);
    }
}

if(!function_exists('str_pad_right'))
{
    function str_pad_right($string, $length, $padString = ' ')
    {
        return str_pad($string, $length, $padString, STR_PAD_RIGHT);
    }
}

if(!function_exists('is_binary'))
{
    function is_binary($string)
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $string) > 0;
    }
}

if(!function_exists('chop_string'))
{
    /**
     * @param string $string Input string
     * @param int $length Maximum string length
     * 
     * @return string Chopped string
     */
    function chop_string($string, $length)
    {
        return strlen($string) > $length ? substr($string, 0, $length) : $string;
    }
}

if(!function_exists('to_camel_case'))
{
    /**
     * @param string $string Snake-cased string
     * @return string Camel cased string
     */
    function to_camel_case($string)
    {
        return preg_replace_callback("/_+(\w)/", function($match) {
            return strtoupper($match[1][0]);
        }, $string);
    }
}

if(!function_exists('to_pascal_case'))
{
    /**
     * @param string $string Snake-cased string
     * @return string Pascal cased string
     */
    function to_pascal_case($string)
    {
        return ucfirst(to_camel_case($string));
    }
}

if(!function_exists('to_snake_case'))
{
    /**
     * @param string $string Pascal or camel cased string
     * @return string Snake cased string
     */
    function to_snake_case($string)
    {
        return ltrim(preg_replace_callback("/([A-Z])/", function($match) {
            return '_' . strtolower($match[1][0]);
        }, $string), '_');
    }
}

if(!function_exists('string_equals'))
{
    /**
     * Compare string with timing attack prevention
     * 
     * @param string $str1
     * @param string $str2
     * 
     * @return bool
     */
    function string_equals($str1, $str2)
    {
        if(strlen($str1) < strlen($str2))
        {
            $str1 = str_pad_right($str1, strlen($str2));
        }
        elseif(strlen($str1) > strlen($str2))
        {
            $str2 = str_pad_right($str2, strlen($str1));
        }

        $equals = true;
        for($i = 0; $i < strlen($str1); $i++)
        {
            $equals = $equals & $str1[$i] === $str2[$i];
        }
        
        return $equals;
    }
}

if(!function_exists('byte_format'))
{
    function byte_format($num, $precision = 1)
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

if(!function_exists('urlsafe_base64_encode'))
{
    function urlsafe_base64_encode($value)
    {
        return strtr(base64_encode($value), '+/=', '._-');
    }
}

if(!function_exists('urlsafe_base64_decode'))
{
    function urlsafe_base64_decode($value)
    {
        return base64_decode(strtr($value, '._-', '+/='));
    }
}

if(!function_exists('remove_invisible_characters'))
{
    /**
     * @param string $string
     * @return string Removed invisible characters
     */
    function remove_invisible_characters($string)
    {
        return preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $string);
    }
}