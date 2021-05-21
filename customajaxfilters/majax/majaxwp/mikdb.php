<?php
namespace CustomAjaxFilters\Majax\MajaxWP;

use stdClass;

class MikDb {	

	private static $pdo;
	private static $dbsettings = array(
		\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
		\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
		\PDO::ATTR_EMULATE_PREPARES => false,
	);
	private static $dbConnSettings=[];
	public static function getConnection() {
        // initialize $pdo on first call
        if (empty(self::$pdo)) {
            self::connect();
        }

        // now we should have a $pdo, whether it was initialized on this call or a previous one
        // but it could have experienced a disconnection
        try {            
            $old_errlevel = error_reporting(0);
            self::$pdo->query("SELECT 1");
        } catch (\PDOException $e) {
            echo "Connection failed, reinitializing...\n";
            self::connect();
        }
        error_reporting($old_errlevel);

        return self::$pdo;
	}
	public static function init($host, $user, $pwd, $database) {
		self::$dbConnSettings["host"]=$host;
		self::$dbConnSettings["user"]=$user;
		self::$dbConnSettings["pwd"]=$pwd;
		self::$dbConnSettings["database"]=$database;
		self::connect();
	}

	public static function connect() {
		if (!isset(self::$dbConnSettings["host"])) die("dbconn settings error");
		if (!isset(self::$dbConnSettings["user"])) die("dbconn settings error");
		if (!isset(self::$dbConnSettings["pwd"])) die("dbconn settings error");
		if (!isset(self::$dbConnSettings["database"])) die("dbconn settings error");
		$host=self::$dbConnSettings["host"];
		$user=self::$dbConnSettings["user"];
		$pwd=self::$dbConnSettings["pwd"];
		$database=self::$dbConnSettings["database"];

		if (!isset(self::$pdo))
		{
			self::$pdo = @new \PDO(
				"mysql:host=$host;dbname=$database",
				$user,
				$pwd,
				self::$dbsettings
			);
		}
	}
	public static function getRows($query, $params = array())	{
		$out = self::$pdo->prepare($query);
		$out->execute($params);
		return $out->fetchAll(\PDO::FETCH_ASSOC);
	}
	public static function getRow($query, $params = array())	{
		$out = self::$pdo->prepare($query);
		$out->execute($params);
		return $out->fetch();
	}
	public static function getInsertSql($table,$fields) {
        $out="INSERT INTO `$table` ";
        $n=0;
        $out.="(";
        foreach ($fields as $f => $val) {
            if ($n>0) $out.=", ";
            $out.="`$f`";
            $n++;
        }
        $out.=")";
        $out.=" VALUES ";
		$out.="(";
		$n=0;
        foreach ($fields as $f => $val) {
            if ($n>0) $out.=", ";
            $out.="'".$val."'";
            $n++;
        }
        $out.=");";
        return $out;
	}	
	public static function getUpdateSql($table,$setFields,$whereFields) {
        $out="UPDATE `$table` SET ";
        $n=0;
        foreach ($setFields as $f => $val) {
            if ($n>0) $out.=", ";
            $out.="`$f`='".$val."'";
            $n++;
		}
		$out.=" WHERE ";
		$n=0;
		foreach ($whereFields as $f => $val) {
            if ($n>0) $out.=" AND ";
            $out.="`$f`='".$val."'";
            $n++;
		}
        $out.=";";
        return $out;
	}	
	public static function createTableIfNotExists($tableName,$fieldsDef,$args=[]) {
		global $wpdb;
		if($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") == $tableName) {            
            return true;
        }
      	MikDb::createTable($tableName,$fieldsDef,$args);
    }
    public static function createTable($tableName,$fieldsDef,$args=[]) {
        global $wpdb;			
		
        $charset_collate = $wpdb->get_charset_collate();
        if (!empty($args["drop"])) $wpdb->query( "DROP TABLE IF EXISTS {$tableName}");
        //check table exists

        $sql = "CREATE TABLE `{$tableName}` (";
        $n=0;
        $primary="";
        foreach ($fieldsDef as $f => $def) {
		 $f="`{$f}`";
		 if ($n>0) $sql.=", ";
		 if (!empty($def["sql"])) {
			$sql.="$f ".$def["sql"];
		 } else {
			$sql.=$f." ".(empty($def["type"]) ? "" : $def["type"]);
			$sql.=(empty($def["notnull"])) ? "" : " NOT NULL";
			$sql.=(empty($def["autoinc"])) ? "" : " AUTO_INCREMENT";
		 }
		 
         if (!empty($def["primary"])) { 
             if ($primary) $primary.=",";
             $primary=$f;
         }
         $n++;
        }
        $sql.=", PRIMARY KEY ($primary)";
		$sql.=") $charset_collate;";
		if (!empty($args["debug"])) echo "<br />sql debug: ".$sql;
        else { 
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}	

	public static function wpdbTableEmpty($tableName,$where="1") {
		global $wpdb;	
		$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tableName} WHERE %s",$where)); 
		if ($count == 0) return true;
		return false;
	}
	public static function wpdbTableCount($tableName,$where="1") {
		global $wpdb;	
		$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tableName} WHERE %s",$where)); 
		return $count;
	}
	public static function getTablePrefix() {
		global $wpdb;	
		$fixPrefix="mauta_";
		if (!empty($wpdb)) return $wpdb->prefix.$fixPrefix;
		return $fixPrefix;
	}
	public static function  getInsertQueryFromArray($table,$mArr,$skipCols=[]) {
		$query="INSERT INTO `$table` SET ";
		$n=0;
		foreach ($mArr as $colName => $mVal) {   
		  //echo "<br />colname:$colName value:$mVal";
		  if (!in_array($colName,$skipCols)) {
			if ($n>0) $query.=",";   
			$query.="`$colName`='$mVal'";
			$n++;
		 }
		}
		return $query;
	}
	public static function insertRow($table,$mArr,$skipCols=[]) {
		global $wpdb;
		$sql=MikDb::getInsertQueryFromArray($table,$mArr,$skipCols);
		$wpdb->get_results($sql);
		return $wpdb->insert_id;
	}
	public static function clearTable($table,$where=[]) {
		global $wpdb;
		if (count($where)<1) $wpdb->query("TRUNCATE TABLE `$table`");
		else {
			$sql="DELETE FROM `$table` WHERE ";
			$n=0;
			foreach ($where as $w) {
				if ($n>0) $sql.=" AND ";
				$sql.=$w;
				$n++;
			}
			$wpdb->query($sql);
		}
	}	
}