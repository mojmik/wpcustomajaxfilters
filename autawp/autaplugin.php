<?php
namespace AutaWP;

class AutaPlugin {
	public static $pluginName="Auta pole";
	public static $prefix="mauta_";
	private static $customPost=[];
	public static $customPostType=["mauta","mauta2", "mycka"];
	public static $textDomain="mauta-plugin";		
       
    function mLoadClass($class) {	
		if (strpos($class,"AutaWP")!==0) return;
		$path=MAUTA_PLUGIN_PATH.str_replace("\\","/",strtolower("$class.php"));		
        require($path);
    }

	
	public function __construct() {			
        spl_autoload_register([$this,"mLoadClass"]);
		register_activation_hook( PLUGIN_FILE_URL_MAUTAWP, [$this,'auta_plugin_install'] );
		
		foreach (AutaPlugin::$customPostType as $cpt) {						
			AutaPlugin::$customPost[]=new AutaCustomPost($cpt); 											
		}		
	}
	function initWP() {
		add_action( 'admin_enqueue_scripts', [$this,'mautaEnqueueStyle'], 11);
		wp_enqueue_script( 'autapluginjs', plugin_dir_url( __FILE__ ) . 'auta-plugin.js', array('jquery') );		
	}
	function mautaEnqueueStyle() {				
		$mStyles=[
			 'mauta' => ['src' => plugin_dir_url( __FILE__ ) . 'mauta.css']			 
		];
		
		foreach ($mStyles as $key => $value) {
			$src = (isset($value["src"])) ? $value["src"] : $value["srcCdn"];
			$key = 'autawp-' . $key;
			wp_register_style($key, $src);
			wp_enqueue_style($key);
		}
	}

	public static function getTable($tab,$cpt="") {
	  global $wpdb;	
	  if ($tab=="main") return $wpdb->prefix."plugin_main";
	  if ($tab=="fields") return $wpdb->prefix.$cpt."_fields";
	  if ($tab=="ajax") return $wpdb->prefix.$cpt."_majax_fields";
	}
	function auta_plugin_install() {
		global $wpdb;			
		$table_name = AutaPlugin::getTable("main"); 
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  name tinytext NOT NULL,
		  text text NOT NULL,
		  url varchar(55) DEFAULT '' NOT NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		$welcome_name = 'Mr. WordPress';
		$welcome_text = 'Congratulations, you just completed the installation!';
		
		

		$wpdb->insert( 
			$table_name, 
			array( 
				'time' => current_time( 'mysql' ), 
				'name' => $welcome_name, 
				'text' => $welcome_text, 
			) 
		);		

		foreach (AutaPlugin::$customPost as $cpt) {
			$cpt->autaFields->makeTable("fields");
			$cpt->autaFields->saveFields("fields");
		}
	}
	 
	
	static function logWrite($val) {
	 file_put_contents(plugin_dir_path( __FILE__ ) . "log.txt",date("d-m-Y h:i:s")." ".$val."\n",FILE_APPEND | LOCK_EX);
	}
}
