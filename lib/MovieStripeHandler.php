<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class MovieStripeHandler extends MovieHandler {
	private $W = 900;
	private $H = 32;

	protected function process(ViewBag $viewbag) {
		$movie = $this->movie;
		$list = $this->list;

		$im = imagecreate($this->W, $this->H);
		$background_color = imagecolorallocate($im, 0xc0, 0xc0, 0xc0);
		imagefilledrectangle($im, 0, 0, $this->W-1, $this->H-1, $background_color);
		$black = imagecolorallocate($im, 0,0,0);
		imagerectangle($im, 0, 0, $this->W-1, $this->H-1, $black);

		$red = imagecolorallocate($im, 0xc0, 0, 0);

		$max = $movie->getMaxSeek();
		$scale = DoubleVal($this->W) / DoubleVal($max);
		foreach($list->getCutRegions() as $v) {
			$left = $v[0];
			$right = $v[1];
			if($right == -1)
			$right = $max;

			$x1 = IntVal($left * $scale);
			$x2 = min($this->W-1, IntVal($right * $scale));

			imagefilledrectangle($im,
			$x1,
			1,
			$x2,
			$this->H - 2,
			$red);
		}

		header("Content-Type: image/png");
		header("Cache-Control: must-revalidate");
		imagepng($im);
		exit;
	}
}
