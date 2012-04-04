<?php

// Logging
class Log {
    private static $fd = null;
    private static $fileNotWritable = false;
    private  static $Prefix = '';

    public static function SetPrefix($prefix) {
	self::$Prefix = $prefix;
    }

    public static function Debug($fmt) {
	self::writeLog(4, $fmt, func_get_args());
    }

    public static function Info($fmt) {
        self::writeLog(3, $fmt, func_get_args());
    }

    public static function Warning($fmt) {
        self::writeLog(2, $fmt, func_get_args());
    }

    public static function Error($fmt) {
        self::writeLog(1, $fmt, func_get_args());
    }

    private static function writeLog($lvl, $fmt, $args) {
	$ts = microtime(true);
	if(!defined("LOGFILE") || LOGFILE == '')
		return;

	if(!defined("LOGLEVEL") || LOGLEVEL < $lvl)
		return;

        if(self::$fileNotWritable === true) 
		return;
	
	if(self::$fd === null) {
		self::$fd = @fopen(LOGFILE, 'a');
		if(!is_resource(self::$fd)) {
			self::$fd = null;
			self::$fileNotWritable = true;
			return;
		}
	}

	static $levels = array('', "ERROR", "WARNING", "INFO", "DEBUG");

	array_shift($args);
	$msg = vsprintf($fmt, $args);
        flock(self::$fd, LOCK_EX);
	$msec = $ts - floor($ts);
	$msec *= 1000;
	$msec = floor($msec);
        fwrite(self::$fd, sprintf("[%s.%03d]\t%s\t%s%s\n",
                            date('Y-m-d H:i:s', floor($ts)),
			    $msec,
                            $levels[$lvl],
			    self::$Prefix,
			    $msg));
        fflush(self::$fd);
        flock(self::$fd, LOCK_UN);
    }
}

