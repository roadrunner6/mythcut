<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class SaveCutlistHandler extends MovieHandler {
	protected function process(Viewbag $viewbag) {
		if(Param('chosen')) {
			if(Param('transcode')) {
				$this->movie->scheduleTranscode();
			}
				
			// Save the cutlist to the database
			$this->movie->saveCutlist();

			$this->Redirect("?action=selectMovie");
		}

		$viewbag->Title = $this->movie->getTitle();
		$this->SetView("SaveCutlist");
	}
}
