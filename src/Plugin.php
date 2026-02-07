<?php

namespace Lvdl\LittleHotelier;

use Lvdl\LittleHotelier\Admin\SettingsPage;
use Lvdl\LittleHotelier\Api\BookingRoute;
use Lvdl\LittleHotelier\Frontend\Shortcode;
use Lvdl\LittleHotelier\Infrastructure\GitHubUpdater;
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
		( new GitHubUpdater() )->init();

		add_action( 'admin_post_lvdl_lh_check_updates', array( $this, 'handle_manual_check_updates' ) );
		add_action( 'admin_post_lvdl_lh_update_now', array( $this, 'handle_manual_update_now' ) );
	}

	public function activate(): void {
		$settings_repo = new SettingsRepository();
		$settings_repo->maybe_seed_defaults();
	}

	public function deactivate(): void {
		// Intentionally no data deletion on deactivation.
	}

	public function handle_manual_check_updates(): void {
		$this->assert_update_permissions_and_nonce( 'lvdl_lh_check_updates_action' );

		delete_site_transient( 'update_plugins' );
		delete_transient( 'lvdl_lh_github_latest_release' );
		wp_update_plugins();

		$this->redirect_to_settings(
			array(
				'lvdl_lh_update_status' => 'checked',
			)
		);
	}

	public function handle_manual_update_now(): void {
		$this->assert_update_permissions_and_nonce( 'lvdl_lh_update_now_action' );

		delete_site_transient( 'update_plugins' );
		delete_transient( 'lvdl_lh_github_latest_release' );
		wp_update_plugins();

		$updates = get_site_transient( 'update_plugins' );
		$item    = is_object( $updates ) && isset( $updates->response[ LVDL_LH_PLUGIN_BASENAME ] ) ? $updates->response[ LVDL_LH_PLUGIN_BASENAME ] : null;
		if ( empty( $item ) ) {
			$this->redirect_to_settings(
				array(
					'lvdl_lh_update_status' => 'no_update',
				)
			);
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->upgrade( LVDL_LH_PLUGIN_BASENAME );

		wp_clean_plugins_cache( true );
		wp_update_plugins();

		if ( true === $result ) {
			$this->redirect_to_settings(
				array(
					'lvdl_lh_update_status' => 'updated',
				)
			);
		}

		$this->redirect_to_settings(
			array(
				'lvdl_lh_update_status' => 'failed',
			)
		);
	}

	private function assert_update_permissions_and_nonce( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage plugin updates.', 'lvdl-little-hotelier' ) );
		}

		check_admin_referer( $action );
	}

	/**
	 * @param array<string,string> $args
	 */
	private function redirect_to_settings( array $args ): void {
		$url = add_query_arg(
			$args,
			admin_url( 'options-general.php?page=lvdl-little-hotelier' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}
