<?php

namespace Zeutzheim\PpintBundle\Util;

class Utils {

    const CAMEL_CASE_PATTERN = '/[a-z]+|[0-9]+|(?:[A-Z][a-z]+)|(?:[A-Z]+(?=(?:[A-Z][a-z])|[^AZa-z]|[$\\d\\n]))/';

	public static function array_add(&$array, $key, $value = 1)
	{
	    if (is_null($array))
            $array = array();
        if (array_key_exists($key, $array))
            $array[$key] += $value;
        else
            $array[$key] = $value;
	}

}
