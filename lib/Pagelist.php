<?php

class Pagelist {
	private $baseurl;
	public $HitsPerPage;
	public $ViewPages;

	public function __construct($baseurl) {
		$this->baseurl = $baseurl;
		$this->HitsPerPage = HPP;
		$this->ViewPages = 3;
	}

	public function Get($pages, $current_page) {
		$min = $current_page - $this->ViewPages;
		$max = $current_page + $this->ViewPages;
		while($min < 1) {
			$min++;
			$max++;
		}
		$max = min($max, $pages);

		while($max > $pages) {
			$min--;
			$max--;
		}
		$min = max($min, 1);

		$result = array();
		if($min > 1) {
			$result[] = $this->GetPageLink(1, '&lt;&lt;');
			$result[] = $this->GetPageLink($current_page-1, '&lt;');
		}

		for($i = $min; $i <= $max; $i++) {
			$result[] = $this->GetPageLink($i == $current_page ? null : $i, $i);
		}

		if($max < $pages) {
			$result[] = $this->GetPageLink($current_page+1, '&gt;');
			$result[] = $this->GetPageLink($pages, '&gt;&gt;');
		}

		return $result;
	}

	private function GetPageLink($page, $label) {
		$c = new StdClass;

		if($page !== null) {
			$href = $this->baseurl;
			$href .= strpos($href, '?') === false ? '?' :'&';
			$href .= 'page=' . $page;
		} else {
			$href = '';
		}
		$c->href = $href;
		$c->Label = $label;
		$c->Page = $page;
		return $c;
	}
}
