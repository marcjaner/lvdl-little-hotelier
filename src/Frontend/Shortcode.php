<?php

namespace Lvdl\LittleHotelier\Frontend;

use Lvdl\LittleHotelier\Service\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode {
	private SettingsRepository $settings_repo;
	private Assets $assets;

	public function __construct( SettingsRepository $settings_repo ) {
		$this->settings_repo = $settings_repo;
		$this->assets        = new Assets();
		add_shortcode( 'lvdl_lh_datepicker', array( $this, 'render' ) );
	}

	/**
	 * @param array<string,mixed> $atts
	 */
	public function render( array $atts = array() ): string {
		$settings = $this->settings_repo->get_settings();
		$atts     = shortcode_atts(
			array(
				'channel_code' => (string) $settings['channel_code'],
				'show_guests'  => 'true',
				'show_promo'   => 'false',
				'button_text'  => __( 'Check Availability', 'lvdl-little-hotelier' ),
				'currency'     => (string) $settings['currency'],
				'locale'       => (string) $settings['locale'],
				'layout'       => 'grid',
				'color'        => '',
				'title'        => '',
			),
			$atts,
			'lvdl_lh_datepicker'
		);

		$config = array(
			'maxStayDays' => absint( $settings['max_stay_days'] ),
		);
		$this->assets->enqueue_widget_assets( $config );

		$context = array(
			'form_id'      => wp_unique_id( 'lvdl-lh-' ),
			'channel_code' => sanitize_text_field( (string) $atts['channel_code'] ),
			'show_guests'  => $this->to_bool( (string) $atts['show_guests'] ),
			'show_promo'   => $this->to_bool( (string) $atts['show_promo'] ),
			'button_text'  => sanitize_text_field( (string) $atts['button_text'] ),
			'currency'     => sanitize_text_field( (string) $atts['currency'] ),
			'locale'       => sanitize_text_field( (string) $atts['locale'] ),
			'title'        => sanitize_text_field( (string) $atts['title'] ),
			'text_color'   => $this->sanitize_color_value( (string) $atts['color'] ),
		);

		ob_start();
		require LVDL_LH_PLUGIN_DIR . 'templates/widget-form.php';
		return (string) ob_get_clean();
	}

	private function to_bool( string $value ): bool {
		return in_array( strtolower( $value ), array( '1', 'true', 'yes', 'on' ), true );
	}

	private function sanitize_color_value( string $value ): string {
		$value = trim( sanitize_text_field( $value ) );
		if ( '' === $value ) {
			return '';
		}

		if ( 1 === preg_match( '/^#[A-Fa-f0-9]{3,8}$/', $value ) ) {
			return $value;
		}

		if ( 1 === preg_match( '/^(?:rgb|rgba|hsl|hsla)\([^()]+\)$/', $value ) ) {
			return $value;
		}

		if ( 1 === preg_match( '/^var\(--[A-Za-z0-9\-_]+\)$/', $value ) ) {
			return $value;
		}

		if ( 1 === preg_match( '/^[A-Za-z]+$/', $value ) ) {
			return strtolower( $value );
		}

		return '';
	}
}
