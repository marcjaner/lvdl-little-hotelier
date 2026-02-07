<?php
/**
 * Plugin Name: LVDL Little Hotelier
 * Description: Date picker and guest picker that redirects to Little Hotelier with prefilled booking parameters.
 * Version: 1.0.5
 * Requires at least: 6.7
 * Requires PHP: 8.0
 * Author: LVDL
 * Text Domain: lvdl-little-hotelier
 * Update URI: https://github.com/marcjaner/lvdl-little-hotelier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LVDL_LH_VERSION', '1.0.5' );
define( 'LVDL_LH_PLUGIN_FILE', __FILE__ );
define( 'LVDL_LH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LVDL_LH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LVDL_LH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( file_exists( LVDL_LH_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once LVDL_LH_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( $class ) {
			$prefix   = 'Lvdl\\LittleHotelier\\';
			$base_dir = LVDL_LH_PLUGIN_DIR . 'src/';

			if ( 0 !== strpos( $class, $prefix ) ) {
				return;
			}

			$relative_class = substr( $class, strlen( $prefix ) );
			$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
}

Lvdl\LittleHotelier\Plugin::get_instance();

register_activation_hook(
	__FILE__,
	array( Lvdl\LittleHotelier\Plugin::get_instance(), 'activate' )
);

register_deactivation_hook(
	__FILE__,
	array( Lvdl\LittleHotelier\Plugin::get_instance(), 'deactivate' )
);
