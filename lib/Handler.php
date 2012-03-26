<?php

/* ===============================================================
 * MythCut 0.13
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

abstract class Handler {
	private $view = null;
	private $viewbag = null;

	protected function Viewbag() {
		if($this->viewbag === null)
		$this->viewbag = new ViewBag();
		return $this->viewbag;
	}

	public function handleRequest() {
		$viewbag = $this->ViewBag();
		$this->process($viewbag);
		$viewbag->RenderView($this->GetView());
	}

	abstract protected function process(ViewBag $viewbag);

	protected function SetView($view) {
		$this->view = $view;
	}

	protected function GetView() {
		return $this->view;
	}

	protected function Redirect($new_url) {
		header("HTTP/1.0 302 Moved");
		header("Location: $new_url");
		exit;
	}
}
