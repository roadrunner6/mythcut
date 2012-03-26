<?php

/* ===============================================================
 * MythCut 0.13
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class Query {
	private $q;
	private $params;
	private static $debugging = false;

	public function __construct($q) {
		$this->q = $q;
		$this->params = array();
	}

	public static function setDebugging($on) {
		self::$debugging = $on;
	}

	public function __set($k, $v) {
		$this->Set($k, $v);
	}

	public function Set($k, $v, $mode = PDO::PARAM_STR) {
		$this->params[] = new SQLParameter($k, $v, $mode);
	}

	public function Execute() {
		if(self::$debugging) {
			echo sprintf("<pre>%s</pre>", html($this));
		}
		$r = DB::query($this->q,
		count($this->params) ? $this->params : null);
		if(!$r instanceof PDOStatement) {
			throw new SQLException($this, DB::ErrorInfo());
		}
		return $r;
	}

	public function Append($q) {
		$this->q .= "\n     ";
		$this->q .= $q;
	}

	public function SingleRow() {
		$r = $this->Execute();
		$item = $r->fetch();
		if(!$item) {
			throw new Exception("Query: expected one result, got none!");
		}

		if($r->fetch()) {
			throw new Exception("Query: expected one result, got more than one!");
		}

		return $item;
	}

	public function Result() {
		$r = $this->Execute();
		$item = $r->fetch(PDO::FETCH_NUM);
		if(!$item) {
			throw new Exception("Query: expected one result, got none!");
		}

		if($r->fetch()) {
			throw new Exception("Query: expected one result, got more than one!");
		}

		if(count($item) != 1) {
			throw new Exception("Query: expected one column, got more than one!");
		}

		return $item[0];
	}

	public function __toString() {
		$s = $this->q;

		foreach(array_reverse($this->params) as $v)  {
			$s = str_replace(':' . $v->name, DB::Instance()->quote($v->val), $s);
		}
		return $s;
	}
}
