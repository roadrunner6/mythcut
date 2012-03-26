<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class SQLException extends Exception {
	public $query;
	public $errorinfo;

	public function __construct($q, $errorinfo) {
		$this->Execute = $q;
		$this->errorinfo = $errorinfo;
	}
}
