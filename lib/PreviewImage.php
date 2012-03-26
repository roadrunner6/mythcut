<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class PreviewImage {
	public $mark;
	public $offset;
	public $cutted;
	public $cutpoint;
	public $seconds;

	const CUT_NONE = 0;
	const CUT_LEFT = -1;
	const CUT_RIGHT = 1;

	function __construct($mark, $offset, $seconds) {
		$this->mark = $mark;
		$this->offset = $offset;
		$this->cutted = false;
		$this->seconds = $seconds;
	}
}

