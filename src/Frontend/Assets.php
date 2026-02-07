<?php

namespace Lvdl\LittleHotelier\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {
	/**
	 * @param array<string,mixed> $config
	 */
	public function enqueue_widget_assets( array $config ): void {
		wp_register_style(
			'lvdl-lh-widget',
			LVDL_LH_PLUGIN_URL . 'assets/css/widget.css',
			array(),
			LVDL_LH_VERSION
		);

		wp_register_script(
			'lvdl-lh-widget',
			LVDL_LH_PLUGIN_URL . 'assets/js/widget.js',
			array(),
			LVDL_LH_VERSION,
			true
		);

		wp_enqueue_style( 'lvdl-lh-widget' );
		wp_enqueue_script( 'lvdl-lh-widget' );

		wp_localize_script(
			'lvdl-lh-widget',
			'lvdlLhWidget',
			array(
				'restUrl'  => esc_url_raw( rest_url( 'lvdl-lh/v1/booking-url' ) ),
				'nonceUrl' => esc_url_raw( rest_url( 'lvdl-lh/v1/booking-nonce' ) ),
				'nonce'    => wp_create_nonce( 'lvdl_lh_booking_nonce' ),
				'i18n'     => array(
					'genericError' => __( 'Something went wrong. Please try again.', 'lvdl-little-hotelier' ),
				),
				'config'   => $config,
			)
		);
	}
}
