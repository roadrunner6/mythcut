<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

class DB {
	private static $db = null;

	public static function Instance() {
		return self::$db;
	}

	public static function ErrorInfo() {
		$info = self::$db->errorInfo();
		return $info[2];
	}
	
	public static function Initialize() {		
		self::InitializeFromEnv();
		
		if(self::Instance() === null) {
			self::InitializeFromConfigXML();
		}
	}
	
	private static function InitializeFromEnv() {
		// Database
		if(getenv("db_name") == '') {
			return;
		}
		
		try {
			$dsn = sprintf('mysql:dbname=%s;host=%s',
						   getenv("db_name"),
						   getenv("db_server"));
			
			$user = getenv("db_login");
			$password = getenv("db_password");

			self::Connect($dsn, $user, $password);			
		} catch (PDOException $e) {
		}
	}

	private static function InitializeFromConfigXML() {
		if(!class_exists("SimpleXMLElement")) {
			throw new Exception("SimpleXML extension is missing");
		}

		$data = file_get_contents("/etc/mythtv/config.xml");
		if($data == '') {
			throw new Exception("Cannot load Mythtv configuration");
		}            
		
		try {
			$xmlreader = new SimpleXMLElement(CONFIG_XML, 0, true);
			$dsn = sprintf('mysql:dbname=%s;host=%s',
							$xmlreader->UPnP->MythFrontend->DefaultBackend->DBName,
							$xmlreader->UPnP->MythFrontend->DefaultBackend->DBHostName);
			
			$user = $xmlreader->UPnP->MythFrontend->DefaultBackend->DBUserName;
			$password = $xmlreader->UPnP->MythFrontend->DefaultBackend->DBPassword;

			self::Connect($dsn, $user, $password);
		} catch (PDOException $e) {
			echo sprintf("Access to myth configuration file %s not possible or database connection not working, exiting...",
			CONFIG_XML);
			exit;
		}
	}

	private static function Connect($dsn, $user, $password) {
		self::$db = new PDO($dsn, $user, $password);
		self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
		$q = new Query("set names 'utf8'");
		$q->Execute();
		$q = new Query("set charset 'utf8'");
		$q->Execute();
	}

	/**
	 * generall Queries
	 * Enter description here ...
	 * @param unknown_type $q
	 */
	public static function Query($q, $params = null) {
		if(!is_array($params)) {
			$r = self::$db->query($q);
			return $r;
		}

		$stmt = self::$db->prepare($q);
		foreach($params as $param) {
			if(! ($param instanceof  SQLParameter)) {
				throw new Exception('Parameter used in PDO bind not instance of SQLParameter');
			}

			$param->Bind($stmt);
		}

		$stmt->execute();
		return $stmt;
	}
}
