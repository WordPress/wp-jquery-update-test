<?php
/*
 * Test jQuery Updates plugin: WP_Jquery_Update_Test class
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

if ( ! class_exists( 'WP_Jquery_Update_Test' ) ) :
class WP_Jquery_Update_Test {

	private static $plugin_dir_name;
	private function __construct() {}

	public static function init_actions() {
		// To be able to replace the src, scripts should not be concatenated.
		if ( ! defined( 'CONCATENATE_SCRIPTS' ) ) {
			define( 'CONCATENATE_SCRIPTS', false );
		}

		$GLOBALS['concatenate_scripts'] = false;

		self::$plugin_dir_name = basename( __DIR__ );

		add_action( 'wp_default_scripts', array( __CLASS__, 'replace_scripts' ), -1 );

		add_action( 'admin_menu', array( __CLASS__, 'add_menu_item' ) );
		add_action( 'network_admin_menu', array( __CLASS__, 'add_menu_item' ) );

		// Add a link to the plugin's settings in the plugins list table.
		add_filter( 'plugin_action_links', array( __CLASS__, 'add_settings_link' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( __CLASS__, 'add_settings_link' ), 10, 2 );

		// Print version info in the console.
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'print_versions' ), 100 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'print_versions' ), 100 );
	}

	public static function replace_scripts( $scripts ) {
		$assets_url = plugins_url( 'assets/', __FILE__ );
		
		// Use 'jquery-core' 4.0.0-beta, and 'jquery-migrate' 3.5.0.
		self::set_script( $scripts, 'jquery', false, array( 'jquery-core', 'jquery-migrate' ), '4.0.0-rc.1' );
		self::set_script( $scripts, 'jquery-core', $assets_url . 'jquery-4.0.0-rc.1.js', array(), '4.0.0-rc.1' );
		self::set_script( $scripts, 'jquery-migrate', $assets_url . 'jquery-migrate-4.0.0-beta.1.js', array(), '4.0.0-beta.1' );
	}

	// Pre-register scripts on 'wp_default_scripts' action, they won't be overwritten by $wp_scripts->add().
	private static function set_script( $scripts, $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
		$script = $scripts->query( $handle, 'registered' );

		if ( $script ) {
			// If already added
			$script->src  = $src;
			$script->deps = $deps;
			$script->ver  = $ver;
			$script->args = $in_footer;

			unset( $script->extra['group'] );

			if ( $in_footer ) {
				$script->add_data( 'group', 1 );
			}
		} else {
			// Add the script
			if ( $in_footer ) {
				$scripts->add( $handle, $src, $deps, $ver, 1 );
			} else {
				$scripts->add( $handle, $src, $deps, $ver );
			}
		}
	}

	// Plugin UI
	public static function settings_ui() {
		?>
		<div class="wrap" style="max-width: 42rem;">

		<h1><?php _e( 'Test jQuery Updates', 'wp-jquery-update-test' ); ?></h1>

		<p>
			<?php _e( 'This plugin is intended for testing of jQuery version 4.0.0 before updating it in WordPress.', 'wp-jquery-update-test' ); ?>
			<?php _e( 'It is not intended for use in production.', 'wp-jquery-update-test' ); ?>
		</p>

		<p>
			<?php _e( 'Currently jQuery 4.0.0-rc.1 and jQuery Migrate 4.0.0-beta.1 are included. An updated version of jQuery UI may be included when it becomes available.', 'wp-jquery-update-test' ); ?>
		</p>
		
		<p>
			<?php _e( 'Activating Test jQuery Updates will replace the current bundled version of jQuery and jQuery Migrate with the versions from this plugin.', 'wp-jquery-update-test' ); ?>
			<?php _e( 'There are no other settings at this time. To stop testing please deactivate the plugin.', 'wp-jquery-update-test' ); ?>
		</p>
		
		<p>
			<?php printf(
				__( 'If you find a bug in a jQuery related script <a href="%s">please report it</a>.', 'wp-jquery-update-test' ),
				esc_url( 'https://github.com/WordPress/wp-jquery-update-test/issues' )
			); ?>
		</p>

		
		<p>
			<?php _e( 'To help with testing this plugin prints information about the currently loaded versions of jQuery, jQuery Migrate, and jQuery UI in the browser console.', 'wp-jquery-update-test' ); ?>
		</p>
		<?php
	}

	public static function add_menu_item() {
		$menu_title = __( 'Test jQuery Updates', 'wp-jquery-update-test' );
		add_plugins_page( $menu_title, $menu_title, 'install_plugins', self::$plugin_dir_name, array( __CLASS__, 'settings_ui' ) );
	}

	public static function add_settings_link( $links, $file ) {
		$plugin_basename = self::$plugin_dir_name . '/wp-jquery-update-test.php';

		if ( $file === $plugin_basename && current_user_can( 'install_plugins' ) ) {
			// Prevent PHP warnings when a plugin uses this filter incorrectly.
			$links   = (array) $links;
			$url     = self_admin_url( 'plugins.php?page=' . self::$plugin_dir_name );
			$links[] = sprintf( '<a href="%s">%s</a>', $url, __( 'Settings', 'wp-jquery-update-test' ) );
		}

		return $links;
	}

	/**
	 * Set defaults on activation.
	 */
	public static function activate() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

		// Clean up old options
		delete_site_option( 'wp-jquery-test-settings' );
	}

	/**
	 * Delete the options on uninstall.
	 */
	public static function uninstall() {
		delete_site_option( 'wp-jquery-test-settings' );
	}

	/**
	 * Print versions info in the console.
	 */
	public static function print_versions() {
		?>
		<script>
		if ( window.console && window.console.log && window.jQuery ) {
			window.jQuery( function( $ ) {
				var jquery = $.fn.jquery || 'unknown';
				var migrate = $.migrateVersion || 'not available';
				var ui = ( $.ui && $.ui.version ) || 'not available';

				window.console.log(
					'WordPress jQuery:', jquery + ',',
					'Migrate:', migrate + ',',
					'UI:', ui
				);
			} );
		}
		</script>
		<?php
	}
}

add_action( 'plugins_loaded', array( 'WP_Jquery_Update_Test', 'init_actions' ) );
endif;
