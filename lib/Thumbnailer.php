<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class Thumbnailer {
	private $channel_id;
	private $starttime;
	private $stream;
	private $w = 200;

	private static $black = null;
	private static $white = null;
	private static $fontsize = 9;
	const ALIGN_LEFT = -1;
	const ALIGN_MIDDLE = 0;
	const ALIGN_RIGHT = 1;
	const ALIGN_TOP = -1;
	const ALIGN_BOTTOM = 1;

	function __construct($channel_id, $starttime, $stream) {
		$this->channel_id = $channel_id;
		$this->starttime = $starttime;
		$this->stream = $stream;
	}

	public function width() {
		return $this->w;
	}

	public function setWidth($w) {
		$this->w = $w;
	}

	public function height() {
		return floor($this->width() * DoubleVal(TN_HEIGHT) / DoubleVal(TN_WIDTH));
	}

	public function getThumbnailURL($offset, $time = '') {
		return sprintf("index.php/TN/%d/%d/%d/%.0f/%d.jpg",
				$this->channel_id,
				$this->starttime,
				$this->width(),
				$offset,
				$time);
	}

	private function getHash() {
		return sha1(sprintf("%s-%d",
			    	    $this->stream,
		   	 	    $this->width()));
	}

	private function handleRequest($offset, $time) {
		Log::SetPrefix(sprintf("Thumbnail(%d): ", getmypid()));
		$hash = $this->getHash();
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $hash) {
			header(getenv("SERVER_PROTOCOL") . " 304 Not Modified");
			header("Content-Length: 0");
			exit;
		}

		$cachedir = CACHE_DIR;
		umask(077);
		if(!is_dir($cachedir)) {
			Log::Info("Creating cache directory %s", $cachedir);
			@mkdir($cachedir);
		}
		$file = $cachedir . '/' . $this->getHash() . '-' . $offset;

		if(is_file($file)) {
			$lm = filemtime($file);

			header("Content-Type: image/jpeg");
			header("Etag: \"" . $this->getHash() . "-" . $offset . "\"");
			header("Last-Modified: " . gmdate('D, d M Y H:i:s T', $lm));
			header("Expires: " . gmdate('D, d M Y H:i:s T', $lm+86400));

			if(array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER) && $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
				header(getenv("SERVER_PROTOCOL") . " 304 Not Modified");
				header("Content-Length: 0");
				Log::Debug("request already satisfied by HTTP_IF_MODIFIED_SINCE header, file=%s", $file);
				exit;
			}

			echo file_get_contents($file);
			exit;
		}

		$outdir = $cachedir . '/' . getmypid();
		if(!is_dir($outdir)) {
			Log::Debug("Creating work directory %s", $outdir);
			mkdir($outdir, 0700);
		}

		$tempfile = new TempFile(".ts");
		$infile = new File($this->stream, false);
		$infile->seek($offset);
		$data = $infile->read(1024*1024);
		$infile->close();
		$tempfile->write($data);

		$cmd = sprintf("%s -nolirc -really-quiet -zoom -quiet -xy %d -vo jpeg:outdir=%s:maxfiles=2 -ao null %s -frames 2 &> /dev/null",
				MPLAYER,
				$this->width(),
				$outdir,
				escapeshellarg($tempfile->Filename()));
		$ts = microtime(true);
		Log::Debug("command=%s", $cmd);
		exec($cmd);
		$elapsed = microtime(true) - $ts;
		Log::Debug("command executed, duration=%.6f sec", $elapsed);
		$tempfile = $this->chooseBestImage($outdir);
		Log::Debug("using image %s", $tempfile);
		if(!is_file($tempfile)) {
			Log::Error("command failed, command was %s", $cmd);
			header("HTTP/1.0 404 not found");
			exit;
		}

		$im = @imagecreatefromjpeg($tempfile);
		$timestring = sprintf("%02d:%02d:%02d",
			     	      floor($time/3600),
 		                      floor(($time % 3600)/60),
		                      $time % 60);
		$this->writeTextAligned($im, self::ALIGN_LEFT, self::ALIGN_TOP, $timestring);

		ob_start();
		imagejpeg($im, '', 60);
		$data = ob_get_contents();
		ob_end_clean();

		$this->cleanDirectory($outdir);

		if($data != '') {
			Log::Debug("finished generation, Size=%d", strlen($data));
			header("Content-Type: image/jpeg");
			file_put_contents($file, $data);
			$lm = filemtime($file);
			header("Last-Modified: " . gmdate('D, d M Y H:i:s T', $lm));
			header("Etag: \"" . $this->getHash() . "-" . $offset . "\"");
			header("Expires: " . gmdate('D, d M Y H:i:s T', $lm+86400));
		} else {
			Log::Error("oops, data is empty, should not happen");
		}

		print($data);
		exit;
	}

	// Some hardware decoders do not work deliver Keyframes at the
	// position delivered in the mythtv recordedseek table, so two
	// frames are written. Guess the better suited, currently just
	// based on the filesize. Not perfect, but ok
	private function chooseBestImage($dir) {
		$i1 = $dir . '/00000001.jpg';
		$i2 = $dir . '/00000002.jpg';
		if(is_file($i1) && !is_file($i2)) return $i1;
		if(!is_file($i1) && is_file($i2)) return $i2; // really, that should not happen!
		$s1 = filesize($i1);
		$s2 = filesize($i2);
		
		if($s1 < $s2 * .5) {
			// first seems to be broken
			Log::Debug("first frame seems to be broken, take the 2nd");
			return $i2;
		}
	
		return $i1;
	}

	private function cleanDirectory($dir) {
		$fn = $dir . '/00000001.jpg';
		Log::Debug($fn);
		if(is_file($fn)) unlink($fn);
		$fn = $dir . '/00000002.jpg';
		if(is_file($fn)) unlink($fn);
		rmdir($dir);
	}

	private function writeText(&$im, $x1, $y1, $x2, $y2, $message) {
		if(self::$white === null) {
			self::$white = ImageColorAllocate ($im, 255, 255, 255);
		}

		if(self::$black === null) {
			self::$black = ImageColorAllocate ($im, 0, 0, 0);
		}

		imagefilledrectangle($im, $x1, $y1, $x2, $y2, self::$black);
		$box = imagettfbbox (self::$fontsize, 0, TN_FONT, $message);
		$width = $box[4] - $box[0];
		$height = $box[1] - $box[7];

		$left = $x1 + ($x2-$x1)/2.0 - $width/2;
		$top = $y1 + ($y2-$y1)/2.0 + $height/2;

		imagettftext($im, 9, 0, $left, $top, self::$white, TN_FONT, $message);
	}

	private function writeTextAligned(&$im, $align_x, $align_y, $message) {
		$box = imagettfbbox (self::$fontsize, 0, TN_FONT, $message);
		$width = $box[4] - $box[0] + 8;
		$height = $box[1] - $box[7] + 6;

		$w = imagesx($im);
		$h = imagesy($im);

		switch($align_x) {
			case self::ALIGN_LEFT: $x1 = 0; $x2 = $width; break;
			case self::ALIGN_MIDDLE: $x1 = Floor( ($w - $width) / 2.0); $x2 = $x1 + $width; break;
			case self::ALIGN_RIGHT: $x2 = $w - 1; $x1 = $x2-$width; break;
			default: $x1 = $align_x; $x2 = $x1 + $width; break;
		}

		switch($align_y) {
			case self::ALIGN_TOP: $y1 = 0; $y2 = $height; break;
			case self::ALIGN_MIDDLE: $y1 = Floor( ($h - $height) / 2.0); $y2 = $y1 + $height;break;
			case self::ALIGN_BOTTOM: $y2 = $height - 1; $y1 = $y2-$height; break;
			default: $y1 = $align_y; $y2 = $y1 + $height; break;
		}

		$this->writeText($im, $x1, $y1, $x2, $y2, $message);
	}

	public static function handleImageRequest() {
		$params = explode("/", $_SERVER['PATH_INFO']);
		array_shift($params);
		list($tn, $channel_id, $starttime, $width, $offset, $time) = $params;
		list($time, $junk) = explode(".", $time);

		if(is_numeric($channel_id) && is_numeric($starttime) && is_numeric($offset) && is_numeric($time)) {
			$movie = new Movie($channel_id, $starttime);
			$instance = $movie->getThumbnailer();
			$instance->setWidth($width);
			$instance->handleRequest($offset, $time);
		}
		exit;
	}

	public static function CleanCache() {
		// Clean the mythcut directory
		if(is_dir(CACHE_DIR)) {
			$dir = opendir(CACHE_DIR);
			while($e = readdir($dir)) {
				$fname = CACHE_DIR . '/' . $e;
				if(is_file($fname) && preg_match('!^[a-f0-9]+-\d+!', $e)) {
					unlink($fname);
				}
			}
			closedir($dir);
		}
	}
}
