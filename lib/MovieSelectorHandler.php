<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class MovieSelectorHandler extends Handler {
	protected function process(ViewBag $viewbag) {
		$this->SetView("MovieSelector");

		$viewbag->Query = $search = Param('search') == '' ? '' : Param('search');

		if(Param('series') === null) {
			$series = isset($_COOKIE['__mc_series']) ?
			trim($_COOKIE['__mc_series']) : '';
			$skip_cutted = isset($_COOKIE['__mc_skip_cutted']) ?
			$_COOKIE['__mc_skip_cutted'] == 'J' : '';
		} else {
			$series = trim(Param('series'));
			$skip_cutted = Param('skip_cutted') == 'J';
		}

		$viewbag->SkipCutted = $skip_cutted;

		if(Param('submit')) {
			list($chanid, $starttime) = explode(".", Param('Movie'));
			$_SESSION['SelectedMovie'] = new SelectedMovie();
			$_SESSION['SelectedMovie']->chanid = $chanid;
			$_SESSION['SelectedMovie']->starttime = $starttime;
			unset($_SESSION['list']);
				
			Thumbnailer::CleanCache();
				
			$this->Redirect('?rand=' . rand(0, 10000000));
		}

		setCookie("__mc_skip_cutted", $skip_cutted?'J' : 'N');
		setCookie("__mc_series", $series);

		$q = new Query("select title, count(1) as cnt, sum(filesize) as size
		        from recorded r
		       where transcoded = 0
		         and deletepending = 0");

		if($skip_cutted) {
			$q->Append("and not exists (select 1 from recordedmarkup m where m.chanid=r.chanid and m.starttime=r.starttime and m.type in (0,1))");
		}

		$q->Append("group by title order by size desc");
		$available_series = array();
		$viewbag->Series = array();
		foreach($q->Execute() as $v) {
			$size = sprintf("%.1f", DoubleVal($v->size) / 1024.0 / 1024.0 / 1024.0);

			$c = new StdClass;
			$c->Title = $v->title;
			$c->Size = $size;
			$c->Recordings = $v->cnt;
			$c->Selected = ($series == $c->Title);
			$viewbag->Series[] = $c;
		}


		$q = new Query("select *,
		             /* CONVERT_TZ('startime','UTC','SYSTEM') as unix */
				unix_timestamp('startime') as unix 
		        from recorded  r
		       where transcoded = 0
			 /* and not storagegroup = 'LiveTV' */
		         and deletepending = 0");

		if($series) {
			$q->Append("and title = :series");
			$q->series = $series;
		}

		$words =preg_split('!\s+!', $viewbag->Query);
		$row = 0;
		foreach($words as $v) {
			$v = trim(chop($v));
			if($v > '') {
				$row++;
				$w1 = '%' . strtr($v, array('%' => '\\%')) . '%';
				$q->Append("and concat(coalesce(title, ''), ' ', coalesce(subtitle, ''), ' ', coalesce(description, '')) like :word" . $row);
				$q->Set('word' . $row, $w1);
			}
		}

		if($skip_cutted) {
			$q->Append(" and not exists (select 1 from recordedmarkup m where m.chanid=r.chanid and m.starttime=r.starttime and m.type in (0,1))");
		}

		$sort_by_size = Param('sort_by_size');
		if($sort_by_size) {
			$q->Append(" order by filesize desc");
		} else {
			$q->Append("order by starttime desc");
		}

		$viewbag->Movies = array();
		foreach($q->Execute() as $item) {
			$c = new StdClass;
			$c->Value = $item->chanid . '.' . $item->unix;
			$c->Title = $item->title;
			$c->Subtitle = $item->subtitle;
			$c->Size = sprintf("%.1f",
			DoubleVal($item->filesize / 1024.0 / 1024.0 / 1024.0));
			$c->Starttime = $item->starttime;

			$viewbag->Movies[] = $c;
		}
	}
}
