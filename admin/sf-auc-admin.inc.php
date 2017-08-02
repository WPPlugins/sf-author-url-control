<?php
if( !defined( 'ABSPATH' ) )
	die( 'Cheatin\' uh?' );

/*----------------------------------------------------------------------------------*/
/* Language support																	*/
/*----------------------------------------------------------------------------------*/

add_action( 'init', 'sf_auc_lang_init' );
function sf_auc_lang_init() {
	load_plugin_textdomain( 'sf-auc', false, SF_AUC_DIRNAME . '/languages/' );
}


/*----------------------------------------------------------------------------------*/
/* Add a "Settings link"															*/
/*----------------------------------------------------------------------------------*/

add_filter( 'plugin_action_links_'.plugin_basename(SF_AUC_FILE), 'sf_auc_settings_action_links', 10, 2 );
add_filter( 'network_admin_plugin_action_links_'.plugin_basename(SF_AUC_FILE), 'sf_auc_settings_action_links', 10, 2 );
function sf_auc_settings_action_links( $links, $file ) {
	$links['settings'] = '<a href="' . admin_url('options-permalink.php') . '">' . __("Settings") . '</a>';
	return $links;
}

/*----------------------------------------------------------------------------------*/
/* Activation message																*/
/*----------------------------------------------------------------------------------*/

add_action( 'load-plugins.php', 'sf_auc_activation_message' );
function sf_auc_activation_message() {
	if ( get_option( 'sf_auc_first_message' ) ) {
		sf_auc_welcome_notice();
		delete_option( 'sf_auc_first_message' );
	}
}


/*----------------------------------------------------------------------------------*/
/* Activation: set a transient for displaying a help message,						*/
/* flush rewrite rules with a possible existing author base							*/
/*----------------------------------------------------------------------------------*/

register_activation_hook( SF_AUC_FILE, 'sf_auc_activation' );
function sf_auc_activation() {
	update_option( 'sf_auc_first_message', 1 );
	sf_auc_author_base();
	flush_rewrite_rules();
}


/*----------------------------------------------------------------------------------*/
/* Deactivation: flush rewrite rules with "author" as author_base					*/
/*----------------------------------------------------------------------------------*/

register_deactivation_hook( SF_AUC_FILE, 'sf_auc_deactivation' );
function sf_auc_deactivation() {
	global $wp_rewrite;
	$wp_rewrite->author_base = 'author';
	flush_rewrite_rules();
}


/*----------------------------------------------------------------------------------*/
/* Uninstall: delete author base option												*/
/*----------------------------------------------------------------------------------*/

register_uninstall_hook( SF_AUC_FILE, 'sf_auc_uninstaller' );
function sf_auc_uninstaller() {
	delete_option( 'author_base' );
}


/*----------------------------------------------------------------------------------*/
/* Register setting and add field to permalinks page								*/
/*----------------------------------------------------------------------------------*/

add_action( 'load-options-permalink.php', 'sf_auc_register_setting' );
function sf_auc_register_setting() {
	register_setting( 'author_base', 'permalink' );
	add_settings_field( 'author_base', __( 'Authors page base', 'sf-auc' ), 'sf_auc_author_base_field', 'permalink', 'optional', array( 'label_for' => 'author_base' ) );
}


/*----------------------------------------------------------------------------------*/
/* Save the author base and display error notices									*/
/*----------------------------------------------------------------------------------*/

add_action('load-options-permalink.php', 'sf_auc_save_author_base');
function sf_auc_save_author_base() {

	if ( isset( $_POST['submit'], $_POST['author_base'] ) && current_user_can( 'manage_options' ) ) {
		check_admin_referer('update-permalink');

		$blog_prefix = is_multisite() && !is_subdomain_install() && is_main_site() ? '/blog' : '';
		$author_base = sanitize_title(trim($_POST['author_base']));

		// Check for identical slug
		if ( $author_base == '' || $author_base == 'author' ) {

			$author_base = 'author';
			delete_option( 'author_base' );

		} else {

			$message = false;
			if ( $author_base == 'tag' || $author_base == 'category' || $author_base == 'archives' )																			// tag, category, archives

				$message = sprintf(__(" (%s? Seriously?)", "sf-auc"), '<em>'.$author_base.'</em>');

			elseif ( !$blog_prefix && get_page_by_path($author_base) )																											// page

				$message =  __(" (for a page)", "sf-auc");

			elseif ( !$blog_prefix && get_taxs_or_pts_by_rewrite( array('rewrite' => $author_base, 'public' => true, '_builtin' => false), 'objects', 'and', 'taxonomies' ) )	// custom taxonomy

				$message = __(" (for a taxonomy)", "sf-auc");

			elseif ( !$blog_prefix && get_post_types( array('has_archive' => $author_base, 'public' => true, '_builtin' => false) ) )											// custom post type archive

				$message = __(" (for a custom post type archive page)", "sf-auc");

			elseif ( trim(get_option( 'permalink_structure' ), '/') == "%postname%" && get_page_by_path($author_base, 'OBJECT', 'post') )										// post

				$message = __(" (for a post)", "sf-auc");

			elseif ( !$blog_prefix && get_taxs_or_pts_by_rewrite( array('rewrite' => $author_base, 'public' => true, '_builtin' => false), 'objects' ) )						// custom post type

				$message = __(" (for a custom post type)", "sf-auc");


			if ( $message ) {
				add_filter('sf_auc_bad_author_base_notice', create_function('', 'return "'.$message.'";'));
				add_action('admin_notices', 'sf_auc_bad_author_base_notice');
				return;
			}

		}

		$author_base = $blog_prefix . '/' . $author_base;

		if ( $author_base != $blog_prefix . '/author' && $author_base != get_option( 'author_base' ) )
			update_option( 'author_base', $author_base );

		global $wp_rewrite;
		$wp_rewrite->author_base = $author_base;
	}
}


/* Get taxonomies or post types by rewrite: the original functions (get_taxonomies and get_post_types) need the full rewrite array to match.
 * With this one we don't need to pass the full array, it can match one or more rewrite attributes (with more than one rewrite attributes, the function will match ALL of them).
 * Parameters:
 * $args     (array):  same as get_taxonomies and get_post_types functions
 * $output   (string): same as get_taxonomies and get_post_types functions: 'names' (default) or 'objects'
 * $operator (string): same as get_taxonomies and get_post_types functions: 'and' (default) or 'or'
 * $type:    (string): use get_post_types or get_taxonomies:                'post_types' (default) or 'taxonomies'
 */
function get_taxs_or_pts_by_rewrite( $args = array(), $output = 'names', $operator = 'and', $type = 'post_types' ) {
	if ( isset($args['rewrite']) && $args['rewrite'] ) {
		$out_output	= $output;
		$output		= 'objects';
		$rewrites	= is_string($args['rewrite']) ? array('slug' => $args['rewrite']) : $args['rewrite'];
		unset($args['rewrite']);
	}

	$objs = $type == 'taxonomies' ? get_taxonomies( $args, $output, $operator ) : get_post_types( $args, $output, $operator );

	if ( isset($rewrites) && count($objs) ) {
		foreach( $objs as $idobj => $obj ) {
			foreach ( $rewrites as $kr => $rewrite ) {
				if ( !isset($obj->rewrite[$kr]) || $obj->rewrite[$kr] != $rewrite ) {
					unset($objs[$idobj]);
					break;
				}
			}
		}
	}

	if ( isset($out_output) && $out_output == 'names' ) {
		$new_objs = array();
		if ( count($objs) ) {
			foreach( $objs as $idobj => $obj ) {
				$new_objs[$idobj] = $idobj;
			}
		}
		$objs = $new_objs;
	}

	return $objs;
}


/*----------------------------------------------------------------------------------*/
/* Print the field in the permalinks settings page									*/
/*----------------------------------------------------------------------------------*/

function sf_auc_author_base_field($args) {							// Setting field
	global $wp_rewrite;
	$blog_prefix = '';
	$author_base = get_option( 'author_base' );
	if ( is_multisite() && !is_subdomain_install() && is_main_site() ) {
		$blog_prefix = '/blog';
		$author_base = preg_replace( '|^/?blog|', '', $author_base );
	}
	$author_base = sanitize_title($author_base);
	echo $blog_prefix . ' <input name="author_base" id="author_base" type="text" value="'.($author_base ? '/'.$author_base : '').'" class="regular-text code"/> <span class="description">('.__( 'Leave empty for default value: author', 'sf-auc' ).')</span>';
}


/*----------------------------------------------------------------------------------*/
/* Add un text field in the user profile											*/
/*----------------------------------------------------------------------------------*/

add_action('show_user_profile', 'sf_auc_edit_user_options');		// Own profile
add_action('edit_user_profile', 'sf_auc_edit_user_options');		// Others
function sf_auc_edit_user_options() {
	global $user_id;
	$user_id = isset($user_id) ? (int) $user_id : 0;

	if ( !current_user_can('edit_users') )			return;
	if ( !($userdata = get_userdata( $user_id )) )	return;

	$def_user_nicename = sanitize_title( $userdata->user_login );
	$blog_prefix = '/';
	$author_base = get_option( 'author_base' ) ? get_option( 'author_base' ) : 'author';
	if ( is_multisite() && !is_subdomain_install() && is_main_site() ) {
		$blog_prefix = '/blog/';
		$author_base = preg_replace( '|^/?blog|', '', $author_base );
	}
	echo '<table class="form-table">'."\n"
			.'<tr>'."\n"
				.'<th><label for="user_nicename">'.__('Profile URL slug', 'sf-auc').'</label></th>'."\n"
				.'<td>'
					.'<code>'.$blog_prefix.sanitize_title($author_base).'/</code>'
					.'<input id="user_nicename" name="user_nicename" class="regular-text code" type="text" value="'.sanitize_title($userdata->user_nicename, $def_user_nicename).'"/> '
					.'<span class="description">('.sprintf(__('Leave empty for default value: %s', 'sf-auc'), $def_user_nicename).')</span> '
					.'<a href="'.get_author_posts_url($user_id).'">'.__('Your Profile').'</a> '
				."</td>\n"
			.'</tr>'."\n"
		.'</table>'."\n";
}


/*----------------------------------------------------------------------------------*/
/* Save user nicename																*/
/*----------------------------------------------------------------------------------*/

add_action('personal_options_update',  'sf_auc_save_user_options');	// Own profile
add_action('edit_user_profile_update', 'sf_auc_save_user_options');	// Others
function sf_auc_save_user_options() {
	$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

	if ( !isset($_POST[ '_wpnonce' ]) || !wp_verify_nonce( $_POST[ '_wpnonce' ], 'update-user_'.$user_id ) )
		return;
	if ( !current_user_can('edit_users') )
		return;
	if ( !isset($_POST['user_nicename']) || !( $userdata = get_userdata( $user_id ) ) )
		return;

	$user_nicename		= $userdata->user_nicename;
	$def_user_nicename	= sanitize_title( $userdata->user_login );

	if ( sanitize_title($_POST['user_nicename'], $def_user_nicename) != $user_nicename )
		$new_nicename = sanitize_title($_POST['user_nicename'], $def_user_nicename);
	else
		return;

	if ( !get_user_by('slug', $new_nicename) ) {
		if ( !wp_update_user( array ('ID' => $user_id, 'user_nicename' => $new_nicename) ) )
			add_action('user_profile_update_errors', 'sf_auc_user_profile_slug_generic_error', 10, 3 );
	} else
		add_action('user_profile_update_errors', 'sf_auc_user_profile_slug_error', 10, 3 );
}


function sf_auc_user_profile_slug_generic_error( $errors, $update, $user ) {
	$errors->add( 'user_nicename', __( '<strong>ERROR</strong>: There was an error updating the author slug. Please try again.', 'sf-auc' ) );
}


function sf_auc_user_profile_slug_error( $errors, $update, $user ) {
	$errors->add( 'user_nicename', __( '<strong>ERROR</strong>: This profile URL slug is already registered. Please choose another one.', 'sf-auc' ) );
}


/*----------------------------------------------------------------------------------*/
/* Notices																			*/
/*----------------------------------------------------------------------------------*/

function sf_auc_welcome_notice() {
	echo '<div class="updated">'."\n"
			.'<p>'.sprintf(__('<strong>SF Author Url Control</strong>: Now you can go to Settings &#8250; %1$sPermalinks</a> to change the authors base url. Also, go to %2$sUsers</a> and chose a user profile, %3$slike your own</a>, for the user&#8217;s slug.', 'sf-auc'), '<a href="'.admin_url('options-permalink.php').'">', '<a href="'.(is_network_admin() ? network_admin_url('users.php') : admin_url('users.php')).'">', '<a href="'.(is_network_admin() ? network_admin_url('profile.php') : admin_url('profile.php')).'">').'</p>'."\n"
		.'</div>';
}

function sf_auc_bad_author_base_notice() {
	$m = apply_filters('sf_auc_bad_author_base_notice', '');
	echo '<div class="error">'."\n"
			.'<p>'.sprintf(__('<strong>ERROR</strong>: This authors page base is already used somewhere else%s. Please choose another one.', 'sf-auc'), $m).'</p>'."\n"
		.'</div>';
}


/*----------------------------------------------------------------------------------*/
/* Columns in users list															*/
/*----------------------------------------------------------------------------------*/

add_filter( 'manage_users_columns',       'sf_auc_manage_users_columns' );
add_filter( 'manage_users_custom_column', 'sf_auc_manage_users_custom_column', 10, 3 );
function sf_auc_manage_users_columns( $defaults ) {
	$defaults['user-nicename'] = __( 'URL slug', 'sf-auc' );
	return $defaults;
}


function sf_auc_manage_users_custom_column( $default, $column_name, $user_id ) {
	if ( $column_name == 'user-nicename' ) {
		$userdata = get_userdata( (int) $user_id );
		$userdata->user_nicename = isset($userdata->user_nicename) && $userdata->user_nicename ? sanitize_title( $userdata->user_nicename ) : '';

		$span = current_user_can('edit_users') && $userdata->user_nicename != sanitize_title($userdata->user_login) ? array( '<span style="color:green">', '</span>' ) : array('','');
		if ( !$userdata->user_nicename ) {
			$span = array( '<span style="color:red;font-weight:bold">', '</span>' );
			$userdata->user_nicename = __('Empty slug!', 'sf-auc');
		}
		$default = $span[0] . $userdata->user_nicename . $span[1];
	}

	return $default;
}
/**/