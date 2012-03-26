<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class LicenseHandler extends Handler {
	protected function process(ViewBag $viewbag) {
		$viewbag->GPLv3 = file_get_contents("misc/LICENSE");
		$this->SetView("License");
	}
}
