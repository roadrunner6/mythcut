<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class ImageList {
	private $items;
	private $movie;
	private $max_mark;
	private $ajax = false;

	function __construct($movie, $restart = false) {
		$this->movie = $movie;
		$this->load($restart);

		$this->computeCutList();
	}

	public function GetItems() {
		return $this->items;
	}

	public function SetAjax($on) {
		$this->ajax = $on;
	}

	private function load($restart = false) {
		if(!isset($_SESSION['list']) || $restart) {
			$_SESSION['list'] = $this->movie->getSeekTable();
		}

		$this->items = $_SESSION['list'];
	}

	private function computeCutList() {
		foreach($this->items as $k => $item) {
			$this->items[$k]->cutted = false;
		}

		foreach($this->items as $key => $item) {
			if($item->cutpoint == PreviewImage::CUT_LEFT) {
				for($i = $key-1; $i >= 0 && $this->items[$i]->cutpoint == PreviewImage::CUT_NONE; $i--) {
					$this->items[$i]->cutted = true;
				}
			} else if($item->cutpoint == PreviewImage::CUT_RIGHT) {
				for($i = $key+1; $i < count($this->items) && $this->items[$i]->cutpoint == PreviewImage::CUT_NONE; $i++) {
					$this->items[$i]->cutted = true;
				}
			}
		}
	}

	public function cutPoints() {
		$r = array();
		foreach($this->cutPointsWithDirection()  as $v)
		$r[] = abs($v);
		return $r;
	}

	public function deleteCutpoint($mark) {
		$key = $this->findItem($mark);
		if($key !== null) {
			$this->items[$key]->cutpoint = PreviewImage::CUT_NONE;
		}

		$this->save_and_redirect($mark);
	}

	public function moveCutpoint($mark) {
		$key = $this->findItem($mark);

		if($key !== null) {
			$l = $key-1;
			$r = $key+1;
			$found_l = null;
			while($l >= 0) {
				if($this->items[$l]->cutpoint != PreviewImage::CUT_NONE) {
					$found_l = $l;
					break;
				}
				$l--;
			}

			$found_r = null;
			$cnt = count($this->items);
			while($r < $cnt) {
				if($this->items[$r]->cutpoint != PreviewImage::CUT_NONE) {
					$found_r = $r;
					break;
				}
				$r++;
			}

			if($found_l != null || $found_r != null) {
				$mark_l = $found_l === null ? -999999999 : $this->items[$found_l]->mark;
				$mark_r = $found_r === null ? 999999999: $this->items[$found_r]->mark;
				$mask = $this->items[$key]->mark;
				if(abs($mark - $mark_l) < abs($mark - $mark_r)) {
					$oldkey = $found_l;
				} else {
					$oldkey = $found_r;
				}

				$this->items[$key]->cutpoint = $this->items[$oldkey]->cutpoint;
				$this->items[$oldkey]->cutpoint = PreviewImage::CUT_NONE;
			}
		}

		$this->save_and_redirect($mark);
	}

	public function cutPointsWithDirection() {
		$r = array();
		foreach($this->items as $v) {
			if($v->cutpoint != PreviewImage::CUT_NONE) {
				$val = $v->mark;
				if($v->cutpoint == PreviewImage::CUT_LEFT)
				$val = -$val;
				$r[] = $val;
			}
		}

		usort($r, array($this, 'compareAbs'));
		return $r;
	}

	private function compareAbs($a, $b) {
		if(abs($a) < abs($b)) return -1;
		if(abs($a) > abs($b)) return 1;
		return 0;
	}

	public function getCutRegions() {
		$state = null;
		$last_state_change = null;
		$regions = array();
		foreach($this->items as $v) {
			if($v->cutted !== $state) {
				if($state !== null || $v->cutted) {
					if(!$v->cutted) {
						$regions[] = array($last_state_change, $v->mark);
					}
				}
				$last_state_change = $v->mark;
			}
			$state = $v->cutted;
		}

		if($state) {
			$regions[] = array($last_state_change, -1);
		}

		return $regions;
	}

	public function cutLeft($mark) {
		$key = $this->findItem($mark);
		if($key !== null) {
			$this->items[$key]->cutpoint = PreviewImage::CUT_LEFT;
		}
		$this->save_and_redirect($mark);
	}

	public function cutRight($mark) {
		$key = $this->findItem($mark);
		if($key !== null) {
			$this->items[$key]->cutpoint = PreviewImage::CUT_RIGHT;
		}

		$this->save_and_redirect($mark);
	}

	public function clearCutlist() {
		foreach($this->items as $k => $v) {
			$this->items[$k]->cutpoint = PreviewImage::CUT_NONE;
		}

		$this->save_and_redirect("");
	}

	private function cut($key_idx, $delta) {
		$state_new = null;
		$key = $key_idx;
		$key += $delta;

		$this->items[$key_idx]->cutpoint = $delta>0 ? PreviewImage::CUT_RIGHT:PreviewImage::CUT_LEFT;

		while($key >= 0 && $key < count($this->items)) {
			if($state_new === null) {
				$state_new = !$this->items[$key]->cutted;
			}

			if($this->items[$key]->cutted == $state_new) {
				break;
			}

			$this->items[$key]->cutted = $state_new;
			$key += $delta;
		}
		$this->save_and_redirect($this->items[$key_idx]->mark);
	}

	public function expandLeft($mark, $all = false) {
		$key = $this->findItem($mark);
		if($key !== null && $key != 0) {
			$this->expand($key-1, $key, $all);
		}
	}

	public function expandRight($mark, $all = false) {
		$key = $this->findItem($mark);
		if($key !== null && $key != count($this->items) - 1) {
			$this->expand($key, $key+1, $all);
		}
	}

	public function printItems($template, $thumbnailer) {
		foreach($this->items as $item) {
			$class = $item->cutted?'cutted':'normal';
			if($item->cutpoint != PreviewImage::CUT_NONE) {
				$class = "cutpoint";
			}
				
			$tr = array(
				'{mark}' => $item->mark,
				'{class}' => $class,
				'{width}' => $thumbnailer->width(),
				'{height}' => $thumbnailer->height(),
				'{url}' => $thumbnailer->getThumbnailURL($item->offset, $item->seconds),
			);
				
			print(strtr($template, $tr));
		}
	}

	private function expand($key1, $key2, $all = false) {
		$item1 = $this->items[$key1];
		$item2 = $this->items[$key2];
		$delta = abs($item2->mark - $item1->mark);

		if($all){
			$skip_frames = 0;
		} else if($delta < 25*5) {
			$skip_frames = 0;
		} else if($delta < 12*25) {
			$skip_frames = 25;
		} else {
			$skip_frames = 144;
		}

		$add_frames = $this->movie->getSeekTable($item1->mark, $item2->mark, $skip_frames);
		$state = $item2->cutted;

		foreach($add_frames as $v) {
			if($this->findItem($v->mark) === null)  {
				$this->items[] = $v;
			}
		}

		usort($this->items, array($this, 'compare'));
		$this->save_and_redirect($item1->mark);
	}

	public function compare($item1, $item2) {
		if($item1->offset < $item2->offset) return -1;
		if($item1->offset > $item2->offset) return 1;
		return 0;
	}

	private function save() {
		// TODO: Something goes wrong here, a reference instead of
		// a copy. Avoid this and copy the list. Must check this.
		$tmp = array();
		foreach($this->items as $v) {
			$c = new PreviewImage($v->mark, $v->offset, $v->seconds);
			$c->cutted = $v->cutted;
			$c->cutpoint = $v->cutpoint;
			$tmp[] = $c;
		}
		$_SESSION['list'] = $tmp;
	}

	private function save_and_redirect($anchor) {
		$this->save();

		// TODO: ugly
		if(Param('action') == 'json')
		return;

		$url = '?';
		if($anchor)
		$url .= "#o" . $anchor;

		echo sprintf('<html><head><script type="text/javascript">location.href="%s"</script></head></html>',
		$url);
		exit;
	}

	private function findItem($mark) {
		foreach($this->items as $k => $v) {
			if($v->mark == $mark) {
				return $k;
			}
		}
		return null;
	}
}

