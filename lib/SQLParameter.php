<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class SQLParameter {
	public $name;
	public $type;
	public $val;

	public function __construct($name, $val, $type = PDO::PARAM_STR) {
		$this->name = $name;
		$this->val = $val;
		$this->type = $type;
	}

	public function Bind(PDOStatement $stmt) {
		$stmt->bindParam(':' . $this->name, $this->val, $this->type);
	}
}
