<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class TempFile extends File {
	private static $ctr = 0;

	public function __construct($extension = '') {
		$dir = "/tmp/mythcut";

		$rand = uniqid(time() . getmypid());

		if(!is_dir($dir)) {
			// may still fail, silently ignore
			@mkdir($dir, 0700);
		}

		do {
			$name = sprintf("%s/%s-%04d%s",
			$dir,
			$rand,
			self::$ctr++,
			$extension
			);
		} while(is_file($name));

		register_shutdown_function(array($this, 'shutdown'));
		parent::__construct($name, true);
	}

	public function shutdown() {
		parent::close();
		if(is_file($this->Filename()))
		unlink($this->Filename());
	}
}
