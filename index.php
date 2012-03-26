<?php

/* ===============================================================
 * MythCut 0.12
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

//ini_set("display_errors", "on");
//ini_set("error_reporting", E_ALL); // & ~E_NOTICE);

require_once 'config.php';

define("VERSION", "0.13");

// CPU Type/Architecture
if((string)PHP_INT_MAX == '9223372036854775807') {
	define("X86_64", true);
} else {
	define("X86_64", false);
}

function html($val) {
	static $tr = array(
		'>' => '&gt;',
		'<' => '&lt;',
		'&' => '&amp;',
		'"' => '&quot;',
	);
	return strtr($val, $tr);
}

function Param($name) {
	if(isset($_REQUEST[$name])) return $_REQUEST[$name];
	return null;
}

function custom_error_handler($number, $string, $file, $line, $context)
{
	if($number != E_NOTICE) {
		throw new ErrorException($string, 0, $number, $file, $line);
	} else {
		if(!preg_match('!^Undefined index: !', $string)) {
			$s = sprintf("<strong>File %s, Line %s: %s</strong><br/>",
			html($file),
			html($line),
			html($string));
			echo "<pre>$s</pre>";
			syslog(LOG_ERR, $s);
		}
	}
}


set_error_handler("custom_error_handler");


/**
 * Class autoloader
 * Loads classes in this directory if requested
 * @param string $class_name Name of the requested class
 * @throws Exception
 */
function __autoload($class_name) {
	if(!preg_match('!^[a-z][a-z_0-9]*$!i', $class_name))
	throw new Exception("Invalid class name: " . $class_name);

	$file = dirname(__FILE__) . '/lib/' . $class_name . '.php';
	if(!is_file($file)) {
		throw new Exception("Class not found: " . $class_name);
	}

	include $file;
}

// Required for packaging/Makefile
if(@isset($argv) && is_array($argv) && $argv[1] == '--version') {
	echo VERSION;
	exit;
}

try {
	ob_start();
	if(!function_exists("json_encode")) {
		throw new Exception("Sorry, you do not have support for JSON in PHP. Please check http://www.php.net/JSON");
	}

	DB::Initialize();
	$action = Param("action");

	if(preg_match('!/index.php/TN/!', $_SERVER['PHP_SELF'])) {
		$action = 'thumbnail';
	} else {
		@session_start();
	}

	if($action === null) {
		if(!($_SESSION['SelectedMovie'] instanceof SelectedMovie)) {
			$action = 'selectMovie';
		} else if(!$_SESSION['SelectedMovie']->valid()) {
			$action = 'selectMovie';
		} else {
			$action = 'movie';
		}
	}

	$handler = null;
	switch($action) {
		case 'json':
			$handler = new JSONHandler(Param('call'));
			break;
				
		case 'thumbnail':
			try {
				Thumbnailer::handleImageRequest();
			}
			catch(Exception $e) {
				// Error-Image
				header("Content-Type: image/jpeg");
				header("Cache-Control: no-cache, must-revalidate");
				print(file_get_contents(dirname(__FILE__) . '/misc/error.jpg'));
			}
			exit;
			break;
		case 'selectMovie':
			$handler = new MovieSelectorHandler();
			break;
		case 'showLicense':
			$handler = new LicenseHandler();
			break;
		case 'showChangelog':
			$handler = new ChangelogHandler();
			break;
		case '':
		case 'moviestripe':
			$handler = new MovieStripeHandler();
			break;
		case 'saveToDB';
		$handler = new SaveCutlistHandler();
		break;

		case 'movieajax':
		case 'movie':
			$handler = new MovieHandler();
			$handler->EnableAjax($action == 'movieajax' || true);
			break;
		default:
			$handler = new ErrorHandler("Invalid action: " . $action);
	}

	$handler->handleRequest();
}
catch(SQLException $e) {
	ob_end_clean();
	$handler = new ErrorHandler($e->query, 'Error in SQL query: ' . $e->errorinfo);
	$handler->handleRequest();
}
catch(Exception $e) {
	ob_end_clean();
	$handler = new ErrorHandler($e->getMessage(), '', $e->getTrace());
	$handler->handleRequest();
}


