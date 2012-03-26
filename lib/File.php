<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class File {
	private $fd = null;
	private $is_write;
	private $filename;

	public function __construct($filename, $write = false) {
		$mode = $write ? 'w' : 'r';
		if(!$write && !file_exists($filename))
		throw new Exception("File not found: " . $filename);
		$this->fd = @fopen($filename, $mode);
		if(!is_resource($this->fd)) {
			$this->fd = null;
			throw new Exception(sprintf("Cannot open file %s with mode %s", $filename, $mode));
		}

		$this->write = $write;
		$this->filename = $filename;
	}

	public function Filename()  {
		return $this->filename;
	}

	public function seek($pos) {
		if(!is_numeric($pos))
		throw new Exception("Invalid seek position: " . $pos);

		if(X86_64) {
			//echo "seeking to pos $pos, infile=" . $this->filename; exit;
			fseek($this->fd, $pos);
		} else {
			$pos = (double)$pos;
			$first = true;
			$maxseek = 2.0 * 1024^3;
			while($pos > 0 || $first) {
				$p =  ($pos > $maxseek) ? $maxseek : $pos;
				$pos -= $p;
				fseek($this->fd,
				$p,
				$first ? SEEK_SET : SEEK_CUR);
				$first = false;
			}
		}
	}

	public function pos() {
		return ftell($this->fd);
	}

	public function eof() {
		return feof($this->fd);
	}

	public function read($numbytes) {
		if($this->write)
		throw new Exception("File not openend for reading: " . $this->filename);
		return fread($this->fd, $numbytes);
	}

	public function write($bytes) {
		if(!$this->write)
		throw new Exception("File not openend for writing: " . $this->filename);
		return fwrite($this->fd, $bytes);
	}

	public function close() {
		if($this->fd) {
			fclose($this->fd);
			$this->fd = null;
		}
	}

	public static function Size($fname) {
		if(X86_64) {
			$result = filesize($fname);
		} else {
			$cmd = sprintf("du -b %s", escapeshellarg($fname));
			$result = DoubleVal(chop(shell_exec($cmd)));
		}

		return $result;
	}
}
