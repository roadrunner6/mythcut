<?php

require_once dirname(__FILE__) . '/minijson.php';

class JSON {
	public static function Decode($var) {
		if(function_exists('json_encode'))
		return json_decode($var);
		return minijson_decode($var);
	}

	public static function Encode($var) {
		if(function_exists('json_encode'))
		return json_encode($var);
		return minijson_encode($var, false);
	}
}
