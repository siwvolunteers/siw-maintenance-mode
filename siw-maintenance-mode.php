<?php
/**
 * SIW Maintenance Mode
 *
 * @package     SIW\Maintenance-Mode
 * @author      Maarten Bruna
 * @copyright   2017-2018 SIW Internationale Vrijwilligersprojecten
 *
 * @wordpress-plugin
 * Plugin Name:	SIW Maintenance Mode
 * Plugin URI:	https://github.com/siwvolunteers/siw-maintenance-mode
 * Description: Maintenance mode voor www.siw.nl
 * Version:     1.3
 * Author:      Maarten Bruna
 * Text Domain: siw
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class om Maintenance Mode te activeren
 * 
 * - Cache uitschakelen
 * - Onderhoudspagina tonen voor niet-ingelogde gebruikers
 * - Cache legen bij activeren en deactiveren van plugin
 */
class SIW_Maintenance_Mode {


	/**
	 * Constructor
	 * 
	 * - Registeer activatie- en deactivatiehooks
	 * - Schakel cache uit
	 * - Toon admin notice
	 * - Toon onderhoudsscherm aan niet-ingelogde gebruiker
	 */
	public function __construct() {
		$this->register_hooks();
		$this->disable_cache();
		$this->show_admin_notice();
		$this->show_maintenance_screen();
	}


	/**
	 * Registeert activatie- en deactivatiehooks
	 *
	 * @return void
	 */
	protected function register_hooks(){
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
	}

	/**
	 * Cache legen als plugin geactiveerd wordt
	 *
	 * @return void
	 */
	public function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		
		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "activate-plugin_{$plugin}" );
		  
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
			remove_action( 'activated_plugin', 'rocket_dismiss_plugin_box' );
		}
	}


	/**
	 * Cache legen (inclusief minified bestanden) en preload starten als plugin gedeactiveerd wordt
	 *
	 * @return void
	 */	
	public function deactivate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "deactivate-plugin_{$plugin}" );
	  
		if ( function_exists( 'rocket_clean_domain' ) && function_exists( 'run_rocket_sitemap_preload' ) ) {
			rocket_clean_domain();
			rocket_clean_minify();
			rocket_clean_cache_busting();
			run_rocket_sitemap_preload();
			remove_action( 'deactivated_plugin', 'rocket_dismiss_plugin_box' );
		}
	}

	/**
	 * Cache uitschakelen
	 *
	 * @return void
	 */
	protected function disable_cache() {
		add_filter( 'do_rocket_generate_caching_files', '__return_false' );
	}
	
	/**
	 * Toon melding dat maintenance mode actief is
	 *
	 * @return void
	 */
	protected function show_admin_notice() {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-warning"><p><b>' . __( 'Maintenance mode is actief.', 'siw' ) . '</b></p></div>';
		});
	}

	/**
	 * Onderhoudsscherm tonen voor niet-ingelogde gebruikers
	 *
	 * @return void
	 */
	protected function show_maintenance_screen() {
		add_action( 'get_header', function() {
			global $pagenow;
			if ( $pagenow == 'wp-login.php' || current_user_can( 'manage_options' ) || is_admin() ) {
				return;
			}
		
			$image_dir = plugin_dir_url( __FILE__ ) . 'images';
			$html = 
				"<h1><img src='{$image_dir}/logo.png' width='150px'></h1>" .
				'<p>' . esc_html__( 'In verband met onderhoud is onze website tijdelijk niet beschikbaar.', 'siw' ) .  '<br> ' .
				esc_html__( 'Onze excuses voor het ongemak.', 'siw' ) . '</p>' ;
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
	}

}

new SIW_Maintenance_Mode;
