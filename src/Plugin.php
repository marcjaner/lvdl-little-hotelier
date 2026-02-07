<?php

namespace Lvdl\LittleHotelier;

use Lvdl\LittleHotelier\Admin\SettingsPage;
use Lvdl\LittleHotelier\Api\BookingRoute;
use Lvdl\LittleHotelier\Frontend\Shortcode;
use Lvdl\LittleHotelier\Service\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {
	private static ?Plugin $instance = null;

	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function init(): void {
		load_plugin_textdomain( 'lvdl-little-hotelier', false, dirname( LVDL_LH_PLUGIN_BASENAME ) . '/languages' );

		$settings_repo = new SettingsRepository();

		new SettingsPage( $settings_repo );
		new Shortcode( $settings_repo );
		new BookingRoute( $settings_repo );
	}

	public function activate(): void {
		$settings_repo = new SettingsRepository();
		$settings_repo->maybe_seed_defaults();
	}

	public function deactivate(): void {
		// Intentionally no data deletion on deactivation.
	}
}
