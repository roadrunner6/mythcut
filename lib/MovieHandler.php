<?php

/* ===============================================================
 * MythCut 0.13
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class MovieHandler extends Handler {
	protected $movie;
	protected $list;
	private   $ajax = false;

	public function __construct() {
		$selectedItem = Param('selectedMovie');
		if($selectedItem != null) {
			list($chanid, $starttime) = explode(".", $selectedItem);
			if(is_numeric($chanid) && is_numeric($starttime)) {
				$_SESSION['SelectedMovie'] = new Movie($chanid, $starttime);
			}
		}

		$this->movie = Movie::Instance();
		$this->list = null;
		if($this->movie !== null) {
			$this->list = $this->movie->getList(Param('startAgain'));

			$this->handleActions();
			$this->movie->fillViewbag($this->Viewbag());
		}
	}

	public function EnableAjax($on) {
		$this->ajax = $on;
	}

	protected function handleActions() {
		if(Param('useCommercialBreaks')) {
			$this->movie->useCommercialBreaks = true;
		}

		if(Param("change_subtitle")) {
			$this->movie->setSubtitle(Param("subtitle"));
			header("Location: index.php");
			exit;
		}

		if(Param('expandLeft')) {
			$this->list->expandLeft(Param('expandLeft'), Param('all') == '1');
		}

		if(Param('expandRight')) {
			$this->list->expandRight(Param('expandRight'), Param('all') == '1');
		}

		if(Param('cutLeft')) {
			$this->list->cutLeft(Param('cutLeft'));
		}

		if(Param('cutRight')) {
			$this->list->cutRight(Param('cutRight'));
		}

		if(Param('clearCutlist')) {
			$this->list->clearCutlist();
		}

		if(Param('deleteCutpoint')) {
			$this->list->deleteCutpoint(Param('deleteCutpoint'));
		}

		if(Param('moveCutpoint')) {
			$this->list->moveCutpoint(Param('moveCutpoint'));
		}
	}

	protected function process(ViewBag $viewbag) {
		$thumbnailer = $this->movie->getThumbnailer();
		$viewbag->Thumbnailer = $thumbnailer;

		// List of defined cutpoints
		$max = $this->movie->getMaxSeek();
		$duration_sec = $this->movie->duration();
		$viewbag->CutList = array();
		foreach($this->list->getCutRegions() as $v) {
			$left = $v[0];
			$right = $v[1];

			$duration_mark = ($right == -1?$max:$right) - $left;
			$secs =  Floor(DoubleVal($duration_mark) / $max * $duration_sec);

			$c = new StdClass;
			$c->Timestamp = sprintf("%02d:%02d:%02d ",
			floor($secs/3600),
			($secs % 3600) / 60,
			$secs % 60);

			$c->Left = $left;
			$c->Right = $right == -1 ? $max : $right;
			$viewbag->CutList[] = $c;
		}

		$this->SetView($this->ajax ? "MovieAjax" : "Movie");
	}
}
