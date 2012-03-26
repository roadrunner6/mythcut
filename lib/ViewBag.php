<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class ViewBag {
	private $file;

	public function __construct() {
		$this->Version = VERSION;
	}

	public function __set($k, $v) {
		$this->$k = $v;
	}

	public function __get($k) {
		throw new Exception(sprintf("Viewbag: %s not defined!", $k));
	}

	public function RenderView($viewname) {
		$file = dirname(__FILE__) . '/../views/' . $viewname . '.php';
		if(!is_file($file)) {
			throw new Exception(sprintf("View %s not found", $viewname));
		}
		$this->file = $file;
		$viewbag = $this;

		include dirname(__FILE__) . '/../views/_main.php';
	}

	public function RenderContent() {
		$viewbag = $this;
		include $this->file;
	}
}

?>
