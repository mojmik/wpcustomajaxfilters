<?php
   /*
   Plugin Name: Custom Ajax Filters
   Plugin URI: https://www.ttj.cz
   description: Adds custom post option, adds custom fields to administration interface v2
  mAuta plugin
   Version: 1.2
   Author: Mik
   Author URI: https://www.ttj.cz
   License: GPL2
   */
   
// Include the core class.
define( 'MAUTA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define('PLUGIN_FILE_URL_MAUTAWP', __FILE__);
define( 'CAF_SHORT_TITLE', 'CAF' );


require_once MAUTA_PLUGIN_PATH . '/customajaxfilters/loader.php';
$loader=new CustomAjaxFilters\Loader();
if (is_admin()) $loader->initAdmin();
else $loader->initFrontend();

