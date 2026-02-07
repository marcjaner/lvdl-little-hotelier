<?php

namespace Lvdl\LittleHotelier\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsRepository {
	public const OPTION_NAME = 'lvdl_lh_settings';

	/**
	 * @return array<string,mixed>
	 */
	public function defaults(): array {
		return array(
			'channel_code'     => '',
			'base_url_template'=> '',
			'region'           => 'apac',
			'currency'         => 'EUR',
			'locale'           => 'en',
			'max_stay_days'    => 28,
			'enable_precheck'  => 'no',
			'cache_duration'   => 900,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_settings(): array {
		$settings = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return wp_parse_args( $settings, $this->defaults() );
	}

	public function maybe_seed_defaults(): void {
		if ( false === get_option( self::OPTION_NAME, false ) ) {
			add_option( self::OPTION_NAME, $this->defaults() );
		}
	}
}
