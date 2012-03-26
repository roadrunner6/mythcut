<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class SelectedMovie {
	public $chanid;
	public $starttime;

	public function valid() {
		return is_numeric($this->chanid) && is_numeric($this->starttime);
	}
}

?>
