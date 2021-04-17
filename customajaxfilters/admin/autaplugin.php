<?php
namespace CustomAjaxFilters\Admin;

class AutaPlugin {		
	private $customPost=[];
	//public static $customPostType=["mauta","mauta2", "mycka"];
	public static $textDomain="mauta-plugin";		
	public static $menuSlug="caf-main-settings";
       
    
	
	public function __construct() {			
        
		register_activation_hook( PLUGIN_FILE_URL_MAUTAWP, [$this,'auta_plugin_install'] );
		add_action('admin_menu' , [$this,'pluginSettingsMenu']); 			
		add_action( 'wp_ajax_createCPT', [$this,'createCPTproc'] );
		add_action( 'wp_ajax_editCPT', [$this,'editCPTproc'] );
		$this->loadCustomPosts();
		//admin		
	}
	
	function loadCustomPosts() {	
		global $wpdb;
		$query = "SELECT * FROM `".AutaPlugin::getTable("main")."`";	
		foreach( $wpdb->get_results($query) as $key => $row) {					
			$this->customPost[]=new AutaCustomPost($row->slug,$row->singular,$row->plural); 											
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
	  if ($tab=="main") return $wpdb->prefix.TAB_PREFIX_BACKEND."plugin_main";
	  if ($tab=="fields") return $wpdb->prefix.TAB_PREFIX_BACKEND.$cpt."_fields";
	  if ($tab=="ajax") return $wpdb->prefix.TAB_PREFIX_FRONTEND.$cpt."_fields";
	}
	function auta_plugin_install() {
		global $wpdb;			
		$table_name = AutaPlugin::getTable("main"); 
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,	
		  slug text NOT NULL,
		  singular text NOT NULL,
		  plural text DEFAULT '' NOT NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

	}
	 
	function pluginSettingsMenu() {    
		//adds menu item
		$page_title = CAF_SHORT_TITLE.' - settings';   
		$menu_title = CAF_SHORT_TITLE.' - settings';   
		$capability = 'manage_options';   
		$menu_slug  = AutaPlugin::$menuSlug;   
		$function   =  [$this,'plugin_settings_page'];   
		$icon_url   = 'dashicons-media-code';   
		$position   = 5;    
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position ); 
		
	}
	function createCPTproc() {
		global $wpdb;
		$cafAction=filter_input( INPUT_POST, "cafAction", FILTER_SANITIZE_STRING );  
		$singular=filter_input( INPUT_POST, "singular", FILTER_SANITIZE_STRING );  
		$plural=filter_input( INPUT_POST, "plural", FILTER_SANITIZE_STRING );  						 				
		if ($cafAction=="create") {
			$slug=sanitize_title($singular);
			$wpdb->insert( 
				AutaPlugin::getTable("main"), 
				array( 
					'slug' => $slug, 
					'singular' => $singular, 
					'plural' => $plural, 
				) 
			);
			$cpt=new AutaCustomPost($slug,$singular,$plural); 				
			$cpt->autaFields->makeTable("fields");
			$cpt->autaFields->saveFields("fields");					
			$this->customPost[]=$cpt;		
			$mess="created!";
		}	
		echo "ok, created";
	}
	function editCPTproc() {
		global $wpdb;
		$cafAction=filter_input( INPUT_POST, "cafAction", FILTER_SANITIZE_STRING );  
		$singular=filter_input( INPUT_POST, "singular", FILTER_SANITIZE_STRING );  
		$plural=filter_input( INPUT_POST, "plural", FILTER_SANITIZE_STRING );  						 				
		$slug=filter_input( INPUT_POST, "slug", FILTER_SANITIZE_STRING );  
		if (isset($_POST["cafActionEdit"])) {
			$mess="updated!";
			$wpdb->update(AutaPlugin::getTable("main"), array('singular' => $singular, 'plural' => $plural), array('slug' => $slug));
			foreach ($this->customPost as $cpt ) {
				if ($cpt->customPostType==$slug) {
					$cpt->singular=$singular;
					$cpt->plural=$plural;					  
				}
			}
		}
		if (isset($_POST["cafActionRemove"])) {
			$mess="removed!";
			$wpdb->delete( AutaPlugin::getTable("main"), array( 'slug' => $slug ) );
			foreach ($this->customPost as $key=>$cpt ) {
				if ($cpt->customPostType==$slug) {
					$keyDel=$key;	
				}
			}
			if (isset($keyDel)) array_splice($this->customPost,$key,1);
		}
	
	}
	function plugin_settings_page() {  
		global $wpdb;
		//$mess=$this->createCPTproc();
		//$mess=$this->editCPTproc();
		?>
		<div>
		Custom posts definition
		<?php 
		if (isset($mess)) { ?>		
		 <h2><?=$mess ?></h2>
		<?php
		}
		?>
		<h2>New custom post</h2>
		<h3>Custom post create</h3>
		<form class='caf-editFieldRow createCPT'>			
			<div><div><label>singular name</label></div><input type='text' name='singular' value='' /></div>	
			<div><div><label>plural name</label></div><input type='text' name='plural' value='' /></div>
			<div><input type='submit' value='create' /></div>
			<input name='cafAction' type='hidden' value='create' />
		</form>
		
		<?php
		if (is_array($this->customPost) && count($this->customPost)>0) {
			?>
			<h3>Custom post edit</h3>
			<?php
			foreach ($this->customPost as $cpt) {			
			?>
			<form method='post' class='caf-editFieldRow editCPT'>
				<div><div><label>singular name</label></div><input type='text' name='singular' value='<?= $cpt->singular?>' /></div>	
				<div><div><label>plural name</label></div><input type='text' name='plural' value='<?= $cpt->plural?>' /></div>
				<div><input name='cafActionEdit' type='submit' value='Edit' /></div>
				<input name='slug' type='hidden' value='<?= $cpt->customPostType?>' />
			</form>
			<form method='post' class='removeCPT'>
				<input name='cafActionRemove' type='submit' value='Remove' />
				<input name='slug' type='hidden' value='<?= $cpt->customPostType?>' />
			</form>			
			<?php
			}
		} else {
		?>
		<h3>Create some custom post types first!</h3>	
		<?php			
		}
		?>
		</div>
		<?php
	}
	
	static function logWrite($val) {
	 file_put_contents(plugin_dir_path( __FILE__ ) . "log.txt",date("d-m-Y h:i:s")." ".$val."\n",FILE_APPEND | LOCK_EX);
	}
}
