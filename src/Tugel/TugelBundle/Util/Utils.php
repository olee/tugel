<?php

namespace Tugel\TugelBundle\Util;

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

	public static function getDirectorySize($dir) {
		if (!file_exists($dir))
			return false;
		exec('du -hs ' . escapeshellarg($dir), $output);
		$size = preg_split('@\\s+@', implode(' ', $output));
		return $size[0];
	}

	public static function rawurlEncode($url) {
	    return str_replace(array('%3A', '%2F'), array(':', '/'), rawurlencode($url));
	}
	
}
