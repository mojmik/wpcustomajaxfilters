<?php
namespace CustomAjaxFilters\Majax\MajaxWP;
use \CustomAjaxFilters\Admin as MajaxAdmin;
   
Class Majax {
	private $ajaxHandler;
	function __construct() {
		spl_autoload_register([$this,"mLoadClass"]);
		if (CAF_MAJAX_FAST > 1) $this->ajaxHandler=new MajaxHandlerShort(); //shortinit lightweight version
		else $this->ajaxHandler=new MajaxHandler(); //ajax-admin version
		$this->ajaxHandler->register();							
	}
	
	function mLoadClass($class) {	
		if (strpos($class,"MajaxWP")!==0) return;
		$path=plugin_dir_path( __FILE__ ) . "majax/".str_replace("\\","/",strtolower("$class.php"));		
        require($path);
	}
	
	function initWP() {		
		add_action('caf_cronhook',[$this,'majax_cron_job']);
				
		
		//init actions		
	
		add_action( 'wp_enqueue_scripts', [$this,'mAjaxEnqueueScripts'] );			
		add_action( 'wp_enqueue_scripts', [$this,'majaxEnqueueStyle'], 11);

		//fronted posts
		$cptAdmin=new MajaxAdmin\AutaCustomPost("zajezd");
		add_action( 'pre_get_posts', [$this,'addCptToQuery'] );			
	}

	function addCptToQuery( $query ) {
		//if ( is_home() && $query->is_main_query() )
		if (  $query->is_main_query() && is_home()  )			
			//$query->set( 'post_type', array( 'post', 'zajezd' ) );
			$query->set( 'post_type', array( 'post', 'zajezd' ) );
		return $query;
	}

	function majaxEnqueueStyle() {		
		$wp_scripts = wp_scripts();	
		$mStyles=[			 
			 'majax' => ['src' => CAF_MAJAX_PLUGIN_URL . 'majax.css'],
			 'select2' => ['src' => CAF_MAJAX_PLUGIN_URL .'select2.min.css', 'srcCdn'=>'http://ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css'],
			 'admin-ui' => [ 'src' => CAF_MAJAX_PLUGIN_URL . "jquery-ui.min.css",
				 			'srcCdn' => 'http://ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/redmond/jquery-ui.css']
		];
		
		foreach ($mStyles as $key => $value) {
			$src = (isset($value["src"])) ? $value["src"] : $value["srcCdn"];
			$key = "majax-" . $key;
			wp_register_style($key, $src);
			wp_enqueue_style($key);
		}
	}

	function mAjaxEnqueueScripts() {	
		$mScripts=[			
			'select2' => [ 'src' => CAF_MAJAX_PLUGIN_URL .'select2.min.js',
						   'srcCdn' => 'https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js',
						   'depends' => array('jquery'),
						   'inFooter' => true

			],
			'jquery-ui-slider' => ['src' => array('jquery'),
								   'inFotter' => true
			],
			'jquery-ui' => [ 'src' => CAF_MAJAX_PLUGIN_URL .'jquery-ui.min.js',
							 'srcCdn' => 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js',
			]

		];
		
		
		foreach ($mScripts as $key => $value) {
			$src = (isset($value["src"])) ? $value["src"] : $value["srcCdn"];
			$version= (isset($value["version"])) ? $value["version"] : '';
			$inFooter= (isset($value["inFooter"])) ? $value["inFooter"] : false;
			$depends= (isset($value["depends"])) ? $value["depends"] : [];
			wp_enqueue_script($key,$src,$depends,$version,$inFooter);
			if (isset($value["localizeObj"])) {
				wp_localize_script( $key, $value["localizeObj"],$value["localizeArray"]);		
			}
		}
		
	}
	
}
	
