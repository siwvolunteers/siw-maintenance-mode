<?php declare(strict_types=1);

/**
 * SIW Maintenance Mode
 *
 * @copyright   2017-2023 SIW Internationale Vrijwilligersprojecten
 *
 * @wordpress-plugin
 * Plugin Name:       SIW Maintenance Mode
 * Plugin URI:        https://github.com/siwvolunteers/siw-maintenance-mode
 * Description:       Maintenance mode voor www.siw.nl
 * Version:           1.4.9
 * Author:            SIW Internationale Vrijwilligersprojecten
 * Author URI:        https://www.siw.nl
 * Text Domain:       siw-maintenance-mode
 * License:           GPLv2 or later
 * Requires at least: 5.5
 * Requires PHP:      7.4
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
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

		add_filter( 'do_rocket_generate_caching_files', '__return_false' );
		add_action( 'admin_notices', [ $this, 'show_admin_notice' ] );
		add_action( 'get_header', [ $this, 'show_maintenance_screen' ] );
		add_action( 'init', [ $this, 'load_textdomain' ] );
	}

	/** Laadt vertalingen */
	public function load_textdomain() {
		load_plugin_textdomain( 'siw-maintenance-mode', false, 'siw-maintenance-mode/languages' );
	}

	/** Cache legen als plugin geactiveerd wordt */
	public function activate() {
		if ( $this->is_wp_rocket_active() ) {
			rocket_clean_domain();
			remove_action( 'activated_plugin', 'rocket_dismiss_plugin_box' );
		}
	}

	/** Cache legen (inclusief minified bestanden) als plugin gedeactiveerd wordt */
	public function deactivate() {
		if ( $this->is_wp_rocket_active() ) {
			rocket_clean_domain();
			rocket_clean_minify();
			rocket_clean_cache_busting();
			remove_action( 'deactivated_plugin', 'rocket_dismiss_plugin_box' );
		}
	}

	/** Controleer of WP Rocket actief is */
	protected function is_wp_rocket_active(): bool {
		return is_plugin_active( 'wp-rocket/wp-rocket.php' );
	}

	/** Toon melding dat maintenance mode actief is */
	public function show_admin_notice() {
		echo '<div class="notice notice-warning"><p><b>' . esc_html__( 'Maintenance mode is actief.', 'siw-maintenance-mode' ) . '</b></p></div>';
	}

	/** Onderhoudsscherm tonen voor niet-ingelogde gebruikers */
	public function show_maintenance_screen() {

		global $pagenow;
		if ( 'wp-login.php' === $pagenow || current_user_can( 'manage_options' ) || is_admin() ) {
			return;
		}

		$logo_url = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );

		$image_dir = esc_url( plugin_dir_url( __FILE__ ) . 'images' );
		$html =
			"<h1><img src='{$logo_url}' title='logo'></h1>" .
			'<p>' . esc_html__( 'In verband met onderhoud is onze website tijdelijk niet beschikbaar.', 'siw-maintenance-mode' ) . '<br> ' .
			esc_html__( 'Onze excuses voor het ongemak.', 'siw-maintenance-mode' ) . '</p>';
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
			width: 80%;
		}
		</style>
		";

		header( sprintf( 'Retry-After: %s', HOUR_IN_SECONDS ) );
		wp_die( $html . $style, esc_html__( 'Onderhoud', 'siw-maintenance-mode' ), \WP_Http::SERVICE_UNAVAILABLE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

new SIW_Maintenance_Mode();
