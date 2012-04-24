<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

//ini_set("display_errors", "on");
//ini_set("error_reporting", E_ALL); // & ~E_NOTICE);

// Configuration settings
if(is_file(dirname(__FILE__) . '/lib/config.php')) 
	require_once dirname(__FILE__) . '/lib/config.php';
else {
	require_once dirname(__FILE__) . '/lib/config-defaults.php';
}

// Logging
require_once dirname(__FILE__) . '/lib/Log.php';

// Database classes
require_once dirname(__FILE__) . '/lib/DB.php';
require_once dirname(__FILE__) . '/lib/SQLParameter.php';
require_once dirname(__FILE__) . '/lib/SQLException.php';
require_once dirname(__FILE__) . '/lib/Query.php';

// File classes
require_once dirname(__FILE__) . '/lib/File.php';
require_once dirname(__FILE__) . '/lib/TempFile.php';

// Misc classes
require_once dirname(__FILE__) . '/lib/CutRegion.php';
require_once dirname(__FILE__) . '/lib/ImageList.php';
require_once dirname(__FILE__) . '/lib/PreviewImage.php';
require_once dirname(__FILE__) . '/lib/SelectedMovie.php';
require_once dirname(__FILE__) . '/lib/ViewBag.php';
require_once dirname(__FILE__) . '/lib/Thumbnailer.php';
require_once dirname(__FILE__) . '/lib/JSON.php';
require_once dirname(__FILE__) . '/lib/Movie.php';
require_once dirname(__FILE__) . '/lib/Pagelist.php';

// Handler classes
require_once dirname(__FILE__) . '/lib/Handler.php';
require_once dirname(__FILE__) . '/lib/ChangelogHandler.php';
require_once dirname(__FILE__) . '/lib/ErrorHandler.php';
require_once dirname(__FILE__) . '/lib/LicenseHandler.php';
require_once dirname(__FILE__) . '/lib/MovieHandler.php';
require_once dirname(__FILE__) . '/lib/JSONHandler.php';
require_once dirname(__FILE__) . '/lib/MovieSelectorHandler.php';
require_once dirname(__FILE__) . '/lib/MovieStripeHandler.php';
require_once dirname(__FILE__) . '/lib/SaveCutlistHandler.php';

define("VERSION", "0.15");

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


