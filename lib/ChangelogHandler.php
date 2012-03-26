<?php

/* ===============================================================
 * MythCut 0.13
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class ChangelogHandler extends Handler {
	protected function process(ViewBag $viewbag) {
		$this->SetView("Changelog");

		$lines = explode("\n", file_get_contents(dirname(__FILE__) . '/../misc/ChangeLog.txt'));
		$release = null;
		$viewbag->Releases = array();
		foreach($lines as $line) {
			if(trim($line) == "") continue;
			if(substr($line, 0, 1) == '*') {
				if($release !== null)
				$release->Items[] = trim(substr($line, 1));
			} else {
				if($release !== null)
				$viewbag->Releases[] = $release;
				$release = new StdClass;
				$release->Items = array();
				list($version, $date) = explode(" - ", $line);
				$release->Version = $version;
				$release->Date = $date;
			}
		}

		if($release !== null)
		$viewbag->Releases[] = $release;
	}
}
