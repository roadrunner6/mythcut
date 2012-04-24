<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class JSONHandler extends MovieHandler {
	private $method;

	public function __construct($call) {
		parent::__construct();
		$this->method = $call;
	}

	public function handleRequest() {
		$result = $this->getJSON();

		// JSON is very good compressable, so use when available
		if(function_exists('ob_gzhandler')) {
			ob_start("ob_gzhandler");
		}

		header("Content-Type: application/json; charset=utf-8");
		header("Cache-Control: private, must-revalidate");
		echo JSON::Encode($result);
		exit;
	}

	protected function process(ViewBag $viewbag) {
		// not needed
	}

	protected function getJSON() {
		switch($this->method) {
			case 'get-thumbnails':
				return $this->getThumbnails();
			case 'getMovieList':
				return $this->getMovieList();
		}

		return $this->errorResult("Invalid method");
	}

	private function getMovieList() {
		$page = (int)Param("page");
		if($page < 1 || $page > 10000)
			$page = 1;

		if(Param("restore") === 'true' && is_object($_SESSION['lastMovieSelection'])) {
			$params = $_SESSION['lastMovieSelection'];
		} else {
  		  $params = new StdClass;
		  $params->search = Param('search');
		  $params->series = Param('series');	     
		  $params->skipTranscoded = Param('skipTranscoded') ? 1 : 0;
		  $params->skipHasCutlist = Param('skipHasCutlist') ? 1 : 0;
		  $params->hpp = (int)Param('hpp') > 0 && (int)Param('hpp') <= 1000 ? 
				 (int)Param('hpp') :
				 HPP;
		}
		$_SESSION['lastMovieSelection'] = $params;

		$q = new Query("select title, chanid,
				             unix_timestamp(starttime) as unix,
				             filesize
				        from recorded  r
				       where deletepending = 0");
	
		if($params->skipTranscoded) {
			$q->Append(" and transcoded=0");
		}

		if(strlen($params->series) > 0) {
			$q->Append("and title = :series");
			$q->series = $params->series;
		}

		$words =preg_split('!\s+!', $params->search);
		$row=0;
		foreach($words as $v) {
			$v = trim(chop($v));
			if($v > '') {
				$row++;
				$w1 = '%' . strtr($v, array('%' => '\\%')) . '%';
				$q->Append("and concat(coalesce(title, ''), ' ', coalesce(subtitle, ''), ' ', coalesce(description, '')) like :word" . $row);
				$q->Set('word' . $row, $w1);
			}
		}

		if($params->skipHasCutlist) {
			$q->Append(" and not exists (select 1 from recordedmarkup m where m.chanid=r.chanid and m.starttime=r.starttime and m.type in (0,1))");
		}

		$sort_by_size = Param('sort_by_size');
		if($sort_by_size) {
			$q->Append(" order by filesize desc");
		} else {
			$q->Append("order by starttime desc");
		}

		$data = array();
		$row = 0;
		$hits = 0;
		$series = array();
		$movies_to_load = array();
		$range_from = ($page-1) * $params->hpp;
		$range_to   = $page * $params->hpp;
		foreach($q->Execute() as $v) {
			$c = new StdClass;
			$c->Chanid = $v->chanid;
			$c->Unixtime = $v->unix;
			$c->Title = $v->title;
			$c->Filesize = $v->filesize;

			$data[] = $c;
			if(!isset($series[$c->Title])) {
				$e = new StdClass;
				$e->Title = $c->Title;
				$e->NumRecordings = 0;
				$e->Filesize = 0.0;
				$e->LastRecording = 0;
				$e->Recordings = array();
				$series[$c->Title] = $e;
			}

			$series[$c->Title]->Filesize += DoubleVal($c->Filesize);
			$series[$c->Title]->LastRecording = max($series[$c->Title]->LastRecording, $c->Unixtime);
			$series[$c->Title]->NumRecordings++;
			$series[$c->Title]->Recordings[] = $c;
				
			if($row >= $range_from && $row < $range_to) {
				$key = sprintf("%d.%d",
				$c->Chanid,
				$c->Unixtime);
				$movies_to_load[$key] = $c;
			}
				
			$row++;
			$hits++;
		}

		if(count($movies_to_load) > 0) {
			$q = new Query("select r.subtitle, r.description, r.chanid,
							  	   unix_timestamp(r.starttime) as unix,
							  	   c.name as channel,
							  	   r.filesize							  	   
							  from recorded r
							  		  left join channel c on (c.chanid = r.chanid)
							 where (
			                         0 = 1");
			$row = 0;
			foreach($movies_to_load as $v) {
				$row++;
				$q->Set("chanid" . $row, $v->Chanid);
				$q->Set("starttime" . $row, date("Y-m-d H:i:s", $v->Unixtime));
				$q->Append(sprintf(" or (r.chanid = :chanid%d and r.starttime=:starttime%s)", $row, $row));
			}
			unset($v);
				
			$q->Append(")");

			foreach($q->Execute() as $v) {
				$key = sprintf("%d.%d", $v->chanid, $v->unix);
				$c = $movies_to_load[$key];
				$c->Subtitle = $v->subtitle;
				$c->Description = $v->description;
				$c->Channel = $v->channel;
				$c->Selector = $key;
				$c->Date = self::JsonDate($v->unix);
				$c->Filesize = (double)$v->filesize;
				$c->FilesizeGB = (double)sprintf("%.1f", $c->Filesize / 1024.0 / 1024.0 / 1024.0);

				$c->IsSeries = ($series[$c->Title]->NumRecordings > 1);
				if($c->IsSeries) {
					$e = new StdClass;
					$e->NumEpisodes = $series[$c->Title]->NumRecordings;
					$e->Filesize = $series[$c->Title]->Filesize;
					$e->FilesizeGB = (double)sprintf("%.1f", $series[$c->Title]->Filesize / 1024.0 / 1024.0 / 1024.0);
					$c->Episodes = $e;
				}

				unset($c);
			}
		}

		$result = new StdClass;
		$result->TotalHits = $hits;
		$result->Pages = Floor( ($hits + $params->hpp - 1) / $params->hpp );
		$result->CurrentPage = $page;
		$result->EntriesPerPage = $params->hpp;
		$result->Movies = array_values($movies_to_load);
		$result->PageList = array();

		$url = sprintf("?action=json&call=getMovieList&tpp=%d&search=%s&series=%s",
				$params->hpp,
				urlencode($params->search),
				urlencode($params->series));
		$pagelist = new Pagelist($url);
		$pagelist->HitsPerPage = $params->hpp;
		$result->BaseHREF = $url;
		$result->PageList = $pagelist->Get($result->Pages, $page);
		
		$result->Params = $params;

		return $this->successResult($result);
	}

	private function compareByDate($a, $b) {
		if($a->LastRecording > $b->LastRecording) return -1;
		if($a->LastRecording < $b->LastRecording) return 1;
		return 0;
	}

	private function getThumbnails() {
		$movie = $this->movie;

		$this->list->SetAjax(true); // prevent redirect.
		$this->handleActions();

		if($movie === null) {
			return $this->errorResult("invalid movie");
		}

		$thumbnailer = $movie->getThumbnailer();
		$data = array();
		$w = $thumbnailer->width();
		$h = $thumbnailer->height();
		foreach($movie->getList()->GetItems() as $v) {
			$c = new StdClass;
			$c->mark = $v->mark;
			$c->offset = $v->offset;
			$c->cutted = $v->cutted;
			$c->href = $thumbnailer->getThumbnailURL($v->offset, $v->seconds);
			$c->w = $w;
			$c->h = $h;
				
			$class = $v->cutted?'cutted':'normal';
			if($v->cutpoint != PreviewImage::CUT_NONE) {
				$class = "cutpoint";
			}
			$c->class = $class;

			$data[] = $c;
		}

		return $this->successResult($data);
	}

	private function successResult($data) {
		return array(
								'success' => true,
								'error' => null,
								'data' => $data
		);
	}

	private function errorResult($error) {
		return array(
						'success' => false,
						'error' => $error,
						'data' => null
		);
	}

	private static function JsonDate($unix) {
		return sprintf("/Date(%.0f000)/", $unix);
	}
}
