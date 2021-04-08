<?php
   /*
   Plugin Name: mAuta plugin
   Plugin URI: http://ttj.cz
   description: Adds custom post option, adds custom fields to administration interface v2
  mAuta plugin
   Version: 1.2
   Author: Mik
   Author URI: http://ttj.cz
   License: GPL2
   */
   
// Include the core class.
define( 'MAUTA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define('PLUGIN_FILE_URL_MAUTAWP', __FILE__);

require_once MAUTA_PLUGIN_PATH . '/autawp/autaplugin.php';

$mautawp=new AutaWP\AutaPlugin(); 
$mautawp->initWP();

