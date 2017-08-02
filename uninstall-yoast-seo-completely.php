<?php
/*
Plugin Name:  Uninstall Yoast SEO Completely
Plugin URI: http://www.jamviet.com/
Description: Simply activate this plugin and click clean up button, it will deactivate Yoast SEO, clean up the WordPress database and then deactivate itself. You will save the disk space in shared hosting !
Version: 1.0
Author: mcjambi
Author URI: http://www.jamviet.com/
*/

// Block direct access to script
if ( ! defined('ABSPATH') ) {
	die(-1);
}

function jam_yoastseo_uninstall_admin_notice() {
	$nonce = wp_create_nonce( 'delete_yoast_database' );
	echo '<div class="updated"><form method="post"><p>* Remember: If you want to use Yoast SEO plugin in the future or want to keep it\'s setting, do NOT click this button !<br>* I understand, please <button class="button action" name="cleanyoastseo" value="true">Clean it UP</button></p><input type="hidden" name="_nonce" value="'.$nonce.'"></form></div>';
}


function jam_yoastseo_uninstall_admin_notice_success() {
	echo '<div class="updated"><p>* All Yoast SEO\'s setting has been remove, i have deactivated myself !</p></div>';
	if (isset($_GET['activate'])) {
		unset($_GET['activate']);
	}
}

// Start safer

function jam_ysrmcl_admin_init() {
	if ( ! isset( $_POST['cleanyoastseo'] ) ) {
		add_action('admin_notices', 'jam_yoastseo_uninstall_admin_notice');
		return ;
	} else {
		$nonce = $_POST['_nonce'];
		// check nonce !
		$verified_nounce = wp_verify_nonce( $nonce, "delete_yoast_database" );
		if ( $verified_nounce )
			jam_run_clean_database_yoast_seo();
		else 
			return;
	}
}

add_action('init', 'jam_ysrmcl_admin_init', 99, 1);


function jam_run_clean_database_yoast_seo() {
	
	// check permission
	if ( ! current_user_can('activate_plugins') )
		return;
	
	require_once(ABSPATH . 'wp-admin/includes/plugin.php');

	// Force deactivate Yoast SEO plugin
	if (is_plugin_active('wordpress-seo/wp-seo.php')) {
		deactivate_plugins('wordpress-seo/wp-seo.php');
	}


	global $wpdb;
	$options_table_name = $wpdb->prefix . "options"; // options
	$usermeta_table_name = $wpdb->prefix . "usermeta"; // Usermeta
	$postmeta_table_name = $wpdb->prefix . "postmeta"; // Post meta ///

	//// Delete entries
	$wpdb->query( sprintf("DELETE FROM %s WHERE option_name like '%wpseo%' OR option_name like '%yst_sm_%'", $options_table_name) );
	$wpdb->query( sprintf("DELETE FROM %s WHERE meta_key like '%wpseo%'", $usermeta_table_name) );
	$wpdb->query( sprintf("DELETE FROM %s WHERE meta_key like '_yoast_wpseo_%'", $postmeta_table_name ) );


	// Yoast SEO has some rule add to .htaccess or PHP by default, but remove .htaccess and recreate it is not safe !
	#flush_rewrite_rules();

	delete_option('rewrite_rules');

	// Remove `wpseo_onpage_fetch` cronjob

	// get the Yoast's cronjob time
	$timestamp = wp_next_scheduled('wpseo_onpage_fetch');
	// Unschedule the cronjob
	wp_unschedule_event($timestamp, 'wpseo_onpage_fetch');



	add_action('admin_notices', 'jam_yoastseo_uninstall_admin_notice_success');



	// Deactivate it's self
	if (is_plugin_active(plugin_basename(__FILE__))) {
		deactivate_plugins(plugin_basename(__FILE__));
	}

}