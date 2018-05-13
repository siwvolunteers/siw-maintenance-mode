<?php
/**
 * Plugin Name: SIW Maintenance Mode
 * Plugin URI: https://github.com/siwvolunteers
 * Description: Maintenance mode voor www.siw.nl
 * Version: 1.0
 * Author: Maarten Bruna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
register_activation_hook( __FILE__, 'siw_maintenance_mode_activation' );
register_deactivation_hook( __FILE__, 'siw_maintenance_mode_deactivation' );

function siw_maintenance_mode_activation() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	
	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "activate-plugin_{$plugin}" );
      
    // Clear WP-Rocket Cache
    if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
		remove_action( 'activated_plugin', 'rocket_dismiss_plugin_box' );
		rocket_dismiss_box( 'rocket_dismiss_plugin_box' );
	}
}

function siw_maintenance_mode_deactivation() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "deactivate-plugin_{$plugin}" );
  
    if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
		run_rocket_sitemap_preload();
		remove_action( 'deactivated_plugin', 'rocket_dismiss_plugin_box' );
		rocket_dismiss_box( 'rocket_dismiss_plugin_box' );
	}
	

}
/**
 * Admin notice dat Maintenance mode actief is.
 */
add_action( 'admin_notices', function () {
	echo '<div class="notice notice-warning"><p><b>' . __( 'Maintenance mode is actief.', 'siw' ) . '</b></p></div>';
});


/* Toon Maintenance scherm voor niet-ingelogde gebruikers */
add_action( 'get_header', function() {
	global $pagenow;
	if ( $pagenow == 'wp-login.php' || current_user_can( 'manage_options' ) || is_admin() ) {
		return;
	}

	$image_dir = plugin_dir_url( __FILE__ ) . 'images';
	$html = 
		"<h1><img src='{$image_dir}/logo.png' width='150px'></h1>" .
		'<p>' . __( 'In verband met onderhoud is onze website tijdelijk niet beschikbaar.', 'siw' ) .  '<br> ' .
		 __('Onze excuses voor het ongemak.', 'siw' ) . '</p>' ;
	$style =
	"<style>
	html{
		background-image: url('{$image_dir}/background.jpg');
		background-repeat: no-repeat;
		background-attachment: fixed;
		background-position: center;
		background-size: cover;
	}
	body{
		text-align: center;
	}
	</style>
	";
	$content = $html . $style;

	header( 'Retry-After: 3600' );
  	wp_die( $content, __( 'Onderhoud', 'siw' ), 503 );

});


