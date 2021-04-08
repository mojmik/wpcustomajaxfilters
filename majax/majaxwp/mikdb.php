<?php
namespace MajaxWP;

use stdClass;

class MikDb {	

	private static $dbconn;
	private static $dbsettings = array(
		\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
		\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
		\PDO::ATTR_EMULATE_PREPARES => false,
	);
	public static function connect($host, $user, $pwd, $database) {
		if (!isset(self::$dbconn))
		{
			self::$dbconn = @new \PDO(
				"mysql:host=$host;dbname=$database",
				$user,
				$pwd,
				self::$dbsettings
			);
		}
	}
	public static function getRows($query, $params = array())	{
		$out = self::$dbconn->prepare($query);
		$out->execute($params);
		return $out->fetchAll(\PDO::FETCH_ASSOC);
	}
	public static function getRow($query, $params = array())	{
		$out = self::$dbconn->prepare($query);
		$out->execute($params);
		return $out->fetch();
	}	
}