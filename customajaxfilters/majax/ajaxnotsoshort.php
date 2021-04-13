<?php
/*
 this feeds ajax from wordpress with minimal loading
*/
namespace CustomAjaxFilters\Majax;

define('SHORTINIT', true);
define('DOING_AJAX', true);

function logWrite($val) {
    file_put_contents("log2.txt",date("d-m-Y h:i:s")." ".$val."\n",FILE_APPEND | LOCK_EX);
 }

//IMPORTANT: Change with the correct path to wp-load.php in your installation
require_once( '../../../wp-load.php' );


// l10n
// ———————————————————————–
require_once( ABSPATH . WPINC . '/l10n.php' );
//require_once( ABSPATH . WPINC . '/class-wp-locale.php' );
//require_once( ABSPATH . WPINC . '/class-wp-locale-switcher.php' );

require_once( ABSPATH . WPINC . '/formatting.php' );
require_once( ABSPATH . WPINC . '/meta.php' );
require_once( ABSPATH . WPINC . '/pluggable.php' );


// User
// ———————————————————————–
require_once( ABSPATH . WPINC . '/user.php' );
require_once( ABSPATH . WPINC . '/capabilities.php' );
require_once( ABSPATH . WPINC . '/class-wp-user.php' );
require_once( ABSPATH . WPINC . '/class-wp-user-query.php' );
require_once( ABSPATH . WPINC . '/class-wp-roles.php' );
require_once( ABSPATH . WPINC . '/class-wp-role.php' );
require_once( ABSPATH . WPINC . '/class-wp-session-tokens.php' );
require_once( ABSPATH . WPINC . '/class-wp-user-meta-session-tokens.php' );


// Posts
// ———————————————————————–
require_once( ABSPATH . WPINC . '/class-wp-query.php' );
require_once( ABSPATH . WPINC . '/class-wp-rewrite.php' );
require_once( ABSPATH . WPINC . '/class-wp-tax-query.php' );

require_once( ABSPATH . WPINC . '/class-wp-post-type.php' );
require_once( ABSPATH . WPINC . '/class-wp-post.php' );
require_once( ABSPATH . WPINC . '/link-template.php' );
require_once( ABSPATH . WPINC . '/author-template.php' );
require_once( ABSPATH . WPINC . '/post.php' );
require_once( ABSPATH . WPINC . '/taxonomy.php' );
require_once( ABSPATH . WPINC . '/post-template.php' );

//pokus
require_once( ABSPATH . WPINC . '/class-wp-ajax-response.php' );
require_once( ABSPATH . WPINC . '/query.php' );
require_once( ABSPATH . WPINC . '/comment.php' );
require_once( ABSPATH . WPINC . '/class-wp-comment.php' );

wp_cookie_constants();
$GLOBALS['wp_the_query'] = new \WP_Query();
$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];

require_once(plugin_dir_path( __FILE__ ) . '/MajaxWP/majaxrender.php');
require_once(plugin_dir_path( __FILE__ ) . '/MajaxWP/majaxhandlershort.php');
require_once(plugin_dir_path( __FILE__ ) . '/MajaxWP/customfields.php');
require_once(plugin_dir_path( __FILE__ ) . '/MajaxWP/customfield.php');
$renderer = new MajaxWP\MajaxWPRender(false);
check_ajax_referer(MajaxWP\MajaxHandlerShort::NONCE,'security');
$action=$_POST["action"];
$renderer->filter_rows_continuous();

