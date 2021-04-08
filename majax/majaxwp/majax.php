<?php
namespace MajaxWP;
   
Class Majax {
	public $thisPluginName="majax";	
	private $ajaxHandler;
	public $postTypeName=["mauta","mauta2","mycka"];
	function __construct() {
		spl_autoload_register([$this,"mLoadClass"]);
		Caching::checkPruneCacheNeeded($this->postTypeName);
		if (MAJAX_FAST > 1) $this->ajaxHandler=new MajaxHandlerShort(); //shortinit lightweight version
		else $this->ajaxHandler=new MajaxHandler(); //ajax-admin version
		$this->ajaxHandler->register();							
	}
	
	function mLoadClass($class) {	
		if (strpos($class,"MajaxWP")!==0) return;
		$path=MAJAX_PLUGIN_PATH.str_replace("\\","/",strtolower("$class.php"));		
        require($path);
	}
	
	function initWP() {		
		add_action('majaxcronhook',[$this,'majax_cron_job']);
		
		register_activation_hook( PLUGIN_FILE_URL, [$this,'majax_plugin_install'] );
		register_deactivation_hook( PLUGIN_FILE_URL, [$this,'majax_plugin_uninstall'] );
		//init actions		
	
		add_action( 'wp_enqueue_scripts', [$this,'mAjaxEnqueueScripts'] );			
		add_action( 'wp_enqueue_scripts', [$this,'majaxEnqueueStyle'], 11);

		
	}

	function majaxEnqueueStyle() {		
		$wp_scripts = wp_scripts();	
		$mStyles=[			 
			 'majax' => ['src' => MAJAX_PLUGIN_URL . 'majax.css'],
			 'select2' => ['src' => MAJAX_PLUGIN_URL .'select2.min.css', 'srcCdn'=>'http://ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css'],
			 'admin-ui' => [ 'src' => MAJAX_PLUGIN_URL . "jquery-ui.min.css",
				 			'srcCdn' => 'http://ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/redmond/jquery-ui.css']
		];
		
		foreach ($mStyles as $key => $value) {
			$src = (isset($value["src"])) ? $value["src"] : $value["srcCdn"];
			$key = MAJAX_PLUGIN_PREFIX . $key;
			wp_register_style($key, $src);
			wp_enqueue_style($key);
		}
	}

	function mAjaxEnqueueScripts() {	
		$mScripts=[			
			'select2' => [ 'src' => MAJAX_PLUGIN_URL .'select2.min.js',
						   'srcCdn' => 'https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js',
						   'depends' => array('jquery'),
						   'inFooter' => true

			],
			'jquery-ui-slider' => ['src' => array('jquery'),
								   'inFotter' => true
			],
			'jquery-ui' => [ 'src' => MAJAX_PLUGIN_URL .'jquery-ui.min.js',
							 'srcCdn' => 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js',
			]

		];
		
		
		foreach ($mScripts as $key => $value) {
			$src = (isset($value["src"])) ? $value["src"] : $value["srcCdn"];
			$version= (isset($value["version"])) ? $value["version"] : '';
			$inFooter= (isset($value["inFooter"])) ? $value["inFooter"] : false;
			wp_enqueue_script($key,$src,$value["depends"],$version,$inFooter);
			if (isset($value["localizeObj"])) {
				wp_localize_script( $key, $value["localizeObj"],$value["localizeArray"]);		
			}
		}
		
	}
	
	function majax_plugin_install() {
		global $wpdb;				
		add_action('majaxcronhook',[$this,'majax_cron_job']);
		if ( ! wp_next_scheduled( 'majaxcronhook' ) ) {
			wp_schedule_event( time(), 'daily', 'majaxcronhook' );
		}
		foreach ($this->postTypeName as $cpt) {
			$table_name = $wpdb->prefix . $cpt ."_majax_fields"; 	
			$charset_collate = $wpdb->get_charset_collate();
	
			$query = "DROP TABLE `$table_name`";   	
			mysqli_query($wpdb->dbh,$query);
			
			$sql = "CREATE TABLE $table_name (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,	
			  name tinytext,
			  value text,
			  title text,
			  type tinytext,
			  compare tinytext,
			  valMin text,
			  valMax text,
			  postType tinytext,
			  filterorder smallint,
			  displayorder smallint,
			  icon text,
			  fieldformat text,
			  PRIMARY KEY  (id)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			Caching::setPostType($cpt);
			Caching::checkPath();
		}
		
	}
	function majax_plugin_uninstall() {
		wp_clear_scheduled_hook( 'majaxcronhook' );
	}
	function majax_cron_job() {
		Caching::pruneCache();
	}
	
}
	
