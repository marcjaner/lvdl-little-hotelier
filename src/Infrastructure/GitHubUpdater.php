<?php

namespace Lvdl\LittleHotelier\Infrastructure;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GitHubUpdater {
	private const REPO_URL             = 'https://github.com/marcjaner/lvdl-little-hotelier';
	private const RELEASE_API_LATEST   = 'https://api.github.com/repos/marcjaner/lvdl-little-hotelier/releases/latest';
	private const TRANSIENT_RELEASE    = 'lvdl_lh_github_latest_release';
	private const TRANSIENT_TTL        = 6 * HOUR_IN_SECONDS;
	private const PLUGIN_SLUG          = 'lvdl-little-hotelier';

	public function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
	}

	/**
	 * @param object $transient
	 * @return object
	 */
	public function inject_update( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( empty( $release['tag_name'] ) ) {
			return $transient;
		}

		$latest_version = ltrim( (string) $release['tag_name'], 'v' );
		if ( version_compare( $latest_version, LVDL_LH_VERSION, '<=' ) ) {
			return $transient;
		}

		$package_url = $this->resolve_package_url( $release );
		if ( '' === $package_url ) {
			return $transient;
		}

		$transient->response[ LVDL_LH_PLUGIN_BASENAME ] = (object) array(
			'slug'        => self::PLUGIN_SLUG,
			'plugin'      => LVDL_LH_PLUGIN_BASENAME,
			'new_version' => $latest_version,
			'url'         => self::REPO_URL,
			'package'     => $package_url,
			'tested'      => get_bloginfo( 'version' ),
			'requires_php'=> '8.0',
		);

		return $transient;
	}

	/**
	 * @param false|object|array $result
	 * @param string             $action
	 * @param object             $args
	 * @return false|object|array
	 */
	public function plugin_info( $result, string $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::PLUGIN_SLUG !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( empty( $release['tag_name'] ) ) {
			return $result;
		}

		$version     = ltrim( (string) $release['tag_name'], 'v' );
		$body        = isset( $release['body'] ) ? (string) $release['body'] : '';
		$package_url = $this->resolve_package_url( $release );

		return (object) array(
			'name'          => 'LVDL Little Hotelier',
			'slug'          => self::PLUGIN_SLUG,
			'plugin'        => LVDL_LH_PLUGIN_BASENAME,
			'version'       => $version,
			'author'        => '<a href="https://github.com/marcjaner">marcjaner</a>',
			'homepage'      => self::REPO_URL,
			'requires'      => '6.7',
			'requires_php'  => '8.0',
			'download_link' => $package_url,
			'sections'      => array(
				'description' => 'Date picker and guest picker redirecting to Little Hotelier with prefilled parameters.',
				'changelog'   => wp_kses_post( nl2br( $body ) ),
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private function get_latest_release(): array {
		$cached = get_transient( self::TRANSIENT_RELEASE );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			self::RELEASE_API_LATEST,
			array(
				'timeout' => 12,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'lvdl-little-hotelier-updater',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return array();
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return array();
		}

		set_transient( self::TRANSIENT_RELEASE, $data, self::TRANSIENT_TTL );

		return $data;
	}

	/**
	 * @param array<string,mixed> $release
	 */
	private function resolve_package_url( array $release ): string {
		if ( isset( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( ! is_array( $asset ) || empty( $asset['name'] ) || empty( $asset['browser_download_url'] ) ) {
					continue;
				}

				$name = (string) $asset['name'];
				if ( str_ends_with( $name, '.zip' ) && str_contains( $name, self::PLUGIN_SLUG ) ) {
					return esc_url_raw( (string) $asset['browser_download_url'] );
				}
			}
		}

		if ( ! empty( $release['zipball_url'] ) ) {
			return esc_url_raw( (string) $release['zipball_url'] );
		}

		return '';
	}
}
