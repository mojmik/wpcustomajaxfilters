<?php
/*
 this feeds ajax from wordpress with minimal loading
*/
namespace CustomAjaxFilters\Majax;


header('Content-Type: text/html');
header( 'X-Content-Type-Options: nosniff' );
header('Cache-Control: no-cache');
header('Pragma: no-cache');

define('SHORTINIT', true);
define('DOING_AJAX', true);


require_once( '../../../../../wp-config.php' );



require_once(plugin_dir_path( __FILE__ ) . '/majaxwp/customfields.php');
require_once(plugin_dir_path( __FILE__ ) . '/majaxwp/customfield.php');
require_once(plugin_dir_path( __FILE__ ) . '/majaxwp/majaxhtmlelements.php');
require_once(plugin_dir_path( __FILE__ ) . '/majaxwp/majaxform.php');
require_once(plugin_dir_path( __FILE__ ) . '/majaxwp/majaxrender.php');
require_once(plugin_dir_path( __FILE__ ) . '/majaxwp/majaxitem.php');
require_once(plugin_dir_path( __FILE__ ) . '/majaxwp/caching.php');
require_once(plugin_dir_path( __FILE__ ) . '/majaxwp/mikdb.php');
require_once(plugin_dir_path( __FILE__ ) . '/majaxwp/imagecache.php');
require_once(plugin_dir_path( __FILE__ ) . '/majaxwp/translating.php');
require_once(plugin_dir_path( __FILE__ ) . '../admin/importcsv.php');
require_once(plugin_dir_path( __FILE__ ) . '../admin/settings.php');
define('CAF_TAB_PREFIX','mauta_');
define('CAF_MAJAX_PATH',plugin_dir_path( __FILE__ ));
//define( 'CAF_MAJAX_PLUGIN_URL', plugin_dir_url( __FILE__ ) . "majax/");

$action=$_POST["action"];
$atts["language"]=(empty($_POST["language"])) ? "" : $_POST["language"];
$atts["type"]=(empty($_POST["mautaCPT"])) ? "" : $_POST["mautaCPT"];

if ($action=="formInit") {
	$renderer = new MajaxWP\MajaxRender(false,$atts); //use false pro preloading hardcoded fields (save one sql query)
	$renderer->showFormFields($_POST["mautaCPT"]);	
}
if ($action=="contact_filled") {
	$renderer = new MajaxWP\MajaxRender(true,$atts); //use false pro preloading hardcoded fields (save one sql query)
	MajaxWP\MikDb::init(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);		
	if (isset($_POST["category"])) {
		$postId=$_POST["category"];	
		$query=$renderer->produceSQL($postId);
		$rows=MajaxWP\Caching::getCachedRows($query);
		$renderer->showRows($rows,0,"single",9,0,"contactFilled");		
	}    
	else {
		//form without posts
		$renderer->showFormFilled("contactFilled","kontakt form");
	}	
	exit;
}
if ($action=="single_row") {
	$renderer = new MajaxWP\MajaxRender(true,$atts); //use false pro preloading hardcoded fields (save one sql query)
	MajaxWP\MikDb::init(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);		
    $query=$renderer->produceSQL($_POST["category"]);
	$rows=MajaxWP\Caching::getCachedRows($query);
	$renderer->showRows($rows,0,"single",9,0,"action");		
	exit;
}
if ($action=="filter_rows") {
	$renderer = new MajaxWP\MajaxRender(true,$atts); //use false pro preloading hardcoded fields (save one sql query)
	MajaxWP\MikDb::init(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);	

    $query=$renderer->produceSQL();
	$rows=MajaxWP\Caching::getCachedRows($query);
	$countsJson=MajaxWP\Caching::getCachedJson("json_$query");
	$countsRows=$renderer->buildCounts($rows,$countsJson);	
	if (!$countsJson) {
		MajaxWP\Caching::addCache("json_$query",$countsRows);
	}
	$renderer->showRows($countsRows,0,"majaxcounts",0);
	$page=intval($_POST["aktPage"]);
	$renderer->showRows($renderer->filterMetaSelects($rows),0,"",9,$page);		
	exit;
}


