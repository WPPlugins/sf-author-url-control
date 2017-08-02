<?php
/*
 * Plugin Name: SF Author Url Control
 * Plugin URI: http://www.screenfeed.fr/auturl/
 * Description: Customize the url of your registered users profile.
 * Version: 1.0.5
 * Author: Grégory Viguier
 * Author URI: http://www.screenfeed.fr/greg/
 * License: GPLv3
 * License URI: http://www.screenfeed.fr/gpl-v3.txt
*/

if( !defined( 'ABSPATH' ) )
	die( 'Cheatin\' uh?' );

define( 'SF_AUC_VERSION',		'1.0.5' );
define( 'SF_AUC_FILE',			__FILE__ );
define( 'SF_AUC_DIRNAME',		basename( dirname( SF_AUC_FILE ) ) );
define( 'SF_AUC_PLUGIN_DIR',	plugin_dir_path( SF_AUC_FILE ) );


/*----------------------------------------------------------------------------------*/
/* Change the "author" base															*/
/*----------------------------------------------------------------------------------*/

add_action('init', 'sf_auc_author_base');
function sf_auc_author_base() {
	global $wp_rewrite;
	$author_base = get_option( 'author_base' );
	$wp_rewrite->author_base = $author_base ? sanitize_title( preg_replace( '|^/?blog|', '', $author_base ) ) : 'author';
}


/*----------------------------------------------------------------------------------*/
/* Administration																	*/
/*----------------------------------------------------------------------------------*/

if ( is_admin() && !( defined('DOING_AJAX') && DOING_AJAX ) )
	include( SF_AUC_PLUGIN_DIR.'/admin/sf-auc-admin.inc.php' );
/**/