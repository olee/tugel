<?php

namespace Tugel\TugelBundle\Util;

class Utils {

	const CAMEL_CASE_PATTERN = '/[a-z]+|[0-9]+|(?:[A-Z][a-z]+)|(?:[A-Z]+(?=(?:[A-Z][a-z])|[^AZa-z]|[$\\d\\n]))/';

	public static function splitTags($text) {
		preg_match_all(Utils::CAMEL_CASE_PATTERN, $text, $matches);
		$tags = array();
		foreach ($matches[0] as $tag)
			Utils::array_add($tags, strtolower($tag));
		return $tags;
	}

	public static function array_add(&$array, $key, $value = 1) {
		//if (is_null($array))
		//	$array = array();
		if (isset($array[$key]))
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

	public static function tailFile($filepath, $lines = 1, $adaptive = true) {
		// Open file
		$f = @fopen($filepath, "rb");
		if ($f === false)
			return false;
		// Sets buffer size
		if (!$adaptive)
			$buffer = 4096;
		else
			$buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
		// Jump to last character
		fseek($f, -1, SEEK_END);
		if (fread($f, 1) != "\n")
			$lines -= 1;

		// Start reading
		$output = '';
		$chunk = '';
		// While we would like more
		while (ftell($f) > 0 && $lines >= 0) {
			// Figure out how far back we should jump
			$seek = min(ftell($f), $buffer);
			// Do the jump (backwards, relative to where we are)
			fseek($f, -$seek, SEEK_CUR);
			// Read a chunk and prepend it to our output
			$output = ($chunk = fread($f, $seek)) . $output;
			// Jump back to where we started reading
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
			// Decrease our line counter
			$lines -= substr_count($chunk, "\n");
		}
		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ($lines++ < 0) {
			// Find first newline and remove all text before that
			$output = substr($output, strpos($output, "\n") + 1);
		}
		// Close file and return
		fclose($f);
		return trim($output);
	}

}
