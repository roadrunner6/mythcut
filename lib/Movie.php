<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class Movie {
	public $chanid;
	public $starttime;
	public $useCommercialBreaks = false;

	private $moviedata = null;
	private $loaded;
	private static $instance = null;

	function __construct($chanid, $starttime) {
		if(!$this->isInt($chanid) || !$this->IsInt($starttime)) {
			throw new Exception(sprintf("Invalid starttime (%s) or channel id (%s) supplied!", $starttime, $chanid));
		}

		$this->chanid = $chanid;
		$this->starttime = $starttime;
		$this->load();
		self::$instance = $this;
	}

	public static function Instance() {
		if(self::$instance === null) {
			try {
				$selmovie = isset($_SESSION['SelectedMovie']) ?
				$_SESSION['SelectedMovie'] :
				null;

				if($selmovie === null || !is_object($selmovie))
					return null;

				self::$instance = new Movie($selmovie->chanid, $selmovie->starttime);
			}
			catch(Exception $e) {
				return null;
			}
		}

		if(self::$instance->isLoaded())
		return self::$instance;
		return null;
	}

	public function isLoaded() {
		return $this->loaded;
	}

	public function getData() {
		return $this->moviedata;
	}

	public function getSeconds() {
		return $this->getData()->ends - $this->getData()->starts;
	}

	public function getList($restart = false, $use_commercial_breaks = false) {
		$list = new ImageList($this, $restart, $use_commercial_breaks);
		return $list;
	}

	public function getMaxSeek() {
		return $this->moviedata->maxmark;
	}

	public function getSeekTable($from_mark = -1, $to_mark = -1, $skip_frames = null) {
		if($skip_frames === null) {
			$duration = $this->moviedata->duration;

			if($duration < 900)
			$skip_frames = 300;
			if($duration < 1800)
			$skip_frames = 700;
			else if($duration < 3600)
			$skip_frames = 1000;
			else
			$skip_frames = 1439;
		}

		// Load existing cutpoints and commercial breaks
		$q = new Query("select *
	                     from recordedmarkup 
	                    where chanid = :chanid 
	                      and starttime = :starttime 
	                      and type in (0, 1, 4, 5) 
	                      order by mark");
		$q->chanid = $this->chanid;
		$q->starttime = date('Y-m-d H:i:s', $this->starttime);
			
		$cutlist_from_db = array();
		$commercials = array();
		foreach($q->Execute() as $v) {
			$t = $v->type;
			if($t == 0 || $t == 1) {
				$cutlist_from_db[$v->mark] = $t;
			} else {
				$commercials[$v->mark] = $t == 5 ? 0 : 1;
			}
		}

		if($this->useCommercialBreaks) {
			$cutlist_from_db = $commercials;
		}

		// Find max mark
		$q = new Query("select max(mark) as maxmark
						  from recordedseek 
						 where chanid = :chanid
						   and starttime = :starttime
						   and type = 9");
		$q->chanid = $this->chanid;
		$q->starttime = date('Y-m-d H:i:s', $this->starttime);
		$max_mark = $q->Result();

		$q = new Query("select *
						  from recordedseek 
						 where chanid = :chanid
						   and starttime = :starttime 
						   and type = 9");
		$q->chanid = $this->chanid;
		$q->starttime = date('Y-m-d H:i:s', $this->starttime);

		// Fixup cutlist to match keyframes
		foreach($cutlist_from_db as $k => $v) {
			$fixed_mark = $this->findNearestKeyframeMark($k, $v == 0 ? -1 : 1);
			if($fixed_mark != $k) {
				$cutlist_from_db[$fixed_mark] = $v;
				unset($cutlist_from_db[$k]);
			}
		}

		if($from_mark != -1) {
			$q->Append("and mark > :from_mark");
			$q->from_mark = $from_mark;
		}

		if($to_mark != -1) {
			$q->Append("and mark < :to_mark");
			$q->to_mark = $to_mark;
		}

		$q->Append("order by mark");

		$frame_idx = 0;
		$result = array();
		$next = -10000;
		$last_mark = null;
		$last = null;
		foreach($q->Execute() as $v) {
			$last = null;

			// Compute approx. seconds for thumbnail generation
			$seconds = IntVal(DoubleVal($this->duration()) / DoubleVal($max_mark) * $v->mark);

			if($v->mark > $next) {
				$c  = new PreviewImage($v->mark, (double)$v->offset, $seconds);
				if(array_key_exists($v->mark, $cutlist_from_db)) {
					$c->cutpoint = $cutlist_from_db[$v->mark] == 0 ?
					PreviewImage::CUT_LEFT : PreviewImage::CUT_RIGHT;
				}
				$result[] = $c;
				$next = $v->mark + $skip_frames;
			} else if(array_key_exists($v->mark, $cutlist_from_db)) {
				$c  = new PreviewImage($v->mark, (double)$v->offset, $seconds);
				if(array_key_exists($v->mark, $cutlist_from_db)) {
					$c->cutpoint = $cutlist_from_db[$v->mark] == 0 ?
					PreviewImage::CUT_LEFT : PreviewImage::CUT_RIGHT;
				}
				$result[] = $c;
			} else {
				$last = $v;
			}
		}

		if($last !== null) {
			$c  = new PreviewImage($last->mark, (double)$last->offset, $seconds);
			if(array_key_exists($last->mark, $cutlist_from_db)) {
				$c->cutpoint = $cutlist_from_db[$last->mark] == 0 ?
				PreviewImage::CUT_LEFT : PreviewImage::CUT_RIGHT;
			}
		}

		if(count($result) > 0 && $result[0]->cutpoint == PreviewImage::CUT_RIGHT) {
			$result[0]->cutpoint = PreviewImage::CUT_NONE;
		}

		return $result;
	}

	public function saveCutlist() {
		$cutregions = $this->getList()->getCutRegions();
		//Query::setDebugging(true);

		$q = new Query("delete from recordedmarkup
						where chanid = :chanid
						 and starttime = :starttime				
						 and type in (-1, 0, 1, 2)
						 and data is null");
		$q->chanid = $this->chanid;
		$q->starttime = $this->moviedata->starttime;
		$q->Execute();

		if(count($cutregions) == 0)
		return;

		$q = new Query("select max(mark)
								  from recordedseek
								  where chanid = :chanid
								    and starttime = :starttime");
		$q->chanid = $this->chanid;
		$q->starttime = $this->moviedata->starttime;
		$last = $q->Result();

		foreach($cutregions as $v) {
			$this->insertCutpoint($v[0], 1);
			$this->insertCutpoint($v[1] == -1 ? $last : $v[1], 0);
		}

		$q = new Query("update recorded
						   set cutlist=1,
							   bookmark = 0
						 where chanid = :chanid
						   and starttime = :starttime");
		$q->chanid = $this->chanid;
		$q->starttime = $this->moviedata->starttime;
		$q->Execute();
	}

	public function getThumbnailer() {
		$thumbnailer = new Thumbnailer($this->chanid, $this->starttime, $this->findStream());
		$thumbnailer->setWidth(TN_WIDTH);
		return $thumbnailer;
	}

	public function getLength() {
		$seconds = $this->Duration();
		return sprintf("%02d:%02d:%02d",
		floor($seconds/3600.0),
		floor(($seconds%3600)/60.0),
		$seconds%60);
	}

	public function getSize() {
		$size = File::Size($this->findStream());
		if($size < 1024*1024*1024) {
			return sprintf("%.1f MB", $size/1024.0/1024.0);
		} else {
			return sprintf("%.1f GB", $size/1024.0/1024.0/1024.0);
		}
	}

	public function Duration() {
		return $this->moviedata->duration;
	}

	public function getChannel() {
		if($this->moviedata->channel_name)
		return $this->moviedata->channel_name;
		return $this->chanid;
	}

	public function getStarttime() {
		return date('d.m.Y H:i:s', $this->starttime);
	}

	public function getStarttimeSQL() {
		return $this->moviedata->starttime;
	}

	public function getTitle()  {
		return $this->moviedata->title;
	}

	public function setSubtitle($title) {
		$q = new Query("update recorded
					       set subtitle = :subtitle
					     where starttime = :starttime 
					       and chanid = :chanid");
		$q->chanid = $this->chanid;
		$q->starttime = $this->getStarttimeSQL();
		$q->subtitle = $title;
		$q->Execute();
	}

	public function getMythLink() {
		return sprintf("/mythweb/tv/detail/%d/%d",
		$this->chanid,
		$this->starttime);
	}

	public function scheduleTranscode() {
		$q = new Query("insert into jobqueue (
								chanid, starttime, inserttime,
								type, cmds, flags, status,
								statustime, hostname, args, comment,
								schedruntime
							) values (
								:chanid, :starttime, CURRENT_TIMESTAMP,
								1, 0, 1, 1,
								CURRENT_TIMESTAMP, '', '', 'Queued via MythCut',
								UTC_TIMESTAMP
							)");
		$q->chanid = $this->chanid;
		$q->starttime = $this->getStarttimeSQL();
		$q->Execute();
	}

	/**
	 * Pre-fill common used variables to a viewbag.
	 * Enter description here ...
	 * @param Viewbag $viewbag
	 */
	public function fillViewbag(Viewbag $viewbag) {
		$list = $this->getList();
		$viewbag->CutPoints = implode(",", $list->cutpoints());
		$viewbag->NumberCutpoints = count($list->cutpoints());
		$viewbag->Length = $this->getLength();
		$viewbag->Size   = $this->getSize();
		$viewbag->Channel = $this->getChannel();
		$viewbag->Starttime = $this->getStarttime();
		$viewbag->Title = $this->getData()->title;
		$viewbag->Subtitle = $this->getData()->subtitle;
		$viewbag->Description = $this->getData()->description;
		$viewbag->List = $list;
	}

	private function isInt($val) {
		if(!is_numeric($val)) {
			return false;
		}

		$v = DoubleVal($val);
		return ($v >= 1 && $v <= 2147483647);
	}

	private function findNearestKeyframeMark($mark, $direction)
    {
        $q = new Query(sprintf("select mark
                                from recordedseek 
                            where chanid = :chanid
                                and starttime = :starttime
                                and type = 9
                                and mark %s :mark1
                                and mark %s :mark2
                            order by abs(:mark1 - mark) limit 1",
            $direction < 0 ? '<=' : '>=',
            $direction < 0 ? '>=' : '<='));
        $q->chanid = $this->chanid;
        $q->starttime = date('Y-m-d H:i:s', $this->starttime);
        $q->mark1 = $mark;
        $q->mark2 = $mark + ($direction < 0 ? -1 : 1) * 100;

        # Check return value, and return $mark itself if needed, but never 'empty' or 'null'
        $resFrame = $mark;
        try {
            $resFrame = $q->Result();
        } catch (Exception $e) {
            Log::Warning("Tried to find keyframe past end of seektable: %s", $e->getMessage());
        }
		return $resFrame;
	}

	private function insertCutpoint($mark, $type) {
		if($mark == -1)
		return;

		$q = new Query("insert into recordedmarkup (
                        chanid, starttime, mark, type,
                        data
                       ) values (
                         :chanid, :starttime, :mark, :type,
                         null
                       )");
		$q->chanid = $this->chanid;
		$q->starttime = $this->moviedata->starttime;
		$q->mark = $mark;
		$q->type = $type;
		$q->Execute();
	}

	private function findStream() {
		Log::Debug("Searching stream for recording %d/%d (%s), storagegroup=%s",
	                   $this->chanid, $this->starttime, $this->moviedata->title, 
			   $this->moviedata->storagegroup);
		$q = new Query("select dirname
                         from storagegroup 
                        where groupname = :storagegroup");
		$q->storagegroup = $this->moviedata->storagegroup;
		foreach($q->Execute() as $v) {
			$fname = sprintf("%s/%s", $v->dirname, $this->moviedata->basename);
			Log::Debug("\tlooking for stream here: %s", $fname);
			if(is_file($fname)) {
				Log::Debug("\tfound stream here: %s", $fname);
				return $fname;
			}
		}
		Log::Debug("\tfailed to find stream for recording");
		return null;
	}

	private function load() {
		$q = new Query("select r.*,
                               unix_timestamp(r.endtime) as ends,
                               unix_timestamp(r.starttime) as starts,
                               unix_timestamp(r.endtime) - unix_timestamp(starttime) as duration,
                               ch.name as channel_name,
                               (select max(mark) 
                                  from recordedseek s
                                 where s.starttime = r.starttime
                                   and s.chanid = r.chanid) as maxmark
                                  from recorded r
                                     left join channel ch on (ch.chanid=r.chanid)
                          where r.chanid = :chanid
                            and r.starttime = :starttime");
		$q->chanid = $this->chanid;
		$q->starttime = date('Y-m-d H:i:s', $this->starttime);
		$r = $q->SingleRow();
		$this->loaded = $r->chanid != '';
		if($this->isLoaded()) {
			$this->moviedata = $r;
		}
	}
}
