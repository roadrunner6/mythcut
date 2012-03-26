<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class ErrorHandler extends Handler {
	private $error;
	private $title;
	private $stacktrace;

	public function __construct($error, $title = '', $stacktrace = null) {
		$this->error = $error;
		$this->title = $title;
		$this->stacktrace = $stacktrace;
	}

	protected function process(ViewBag $viewbag) {
		if($this->error instanceOf Exception) {
			$viewbag->Error = print_r($this->error, true);
		} else {
			$viewbag->Error = html($this->error);
		}
		$viewbag->ErrorTitle = $this->title;
		$viewbag->Stacktrace = $this->stacktrace;

		$this->SetView("Error");

	}
}
