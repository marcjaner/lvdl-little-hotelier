<?php

namespace Lvdl\LittleHotelier\Api;

use Lvdl\LittleHotelier\Domain\BookingRequest;
use Lvdl\LittleHotelier\Domain\DateRange;
use Lvdl\LittleHotelier\Domain\GuestCounts;
use Lvdl\LittleHotelier\Domain\ValidationException;
use Lvdl\LittleHotelier\Service\SettingsRepository;
use Lvdl\LittleHotelier\Service\UrlBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookingRoute {
	private SettingsRepository $settings_repo;
	private UrlBuilder $url_builder;

	public function __construct( SettingsRepository $settings_repo ) {
		$this->settings_repo = $settings_repo;
		$this->url_builder   = new UrlBuilder();

		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	public function register_route(): void {
		register_rest_route(
			'lvdl-lh/v1',
			'/booking-url',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'checkInDate'  => array( 'required' => true, 'type' => 'string' ),
					'checkOutDate' => array( 'required' => true, 'type' => 'string' ),
					'adults'       => array( 'required' => true, 'type' => 'integer' ),
					'children'     => array( 'required' => false, 'type' => 'integer' ),
					'infants'      => array( 'required' => false, 'type' => 'integer' ),
				),
			)
		);

		register_rest_route(
			'lvdl-lh/v1',
			'/booking-nonce',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_nonce' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle( \WP_REST_Request $request ) {
		$header_nonce = sanitize_text_field( (string) $request->get_header( 'X-WP-Nonce' ) );
		$body_nonce   = sanitize_text_field( (string) $request->get_param( 'nonce' ) );
		$nonce        = '' !== $header_nonce ? $header_nonce : $body_nonce;

		if ( ! wp_verify_nonce( $nonce, 'lvdl_lh_booking_nonce' ) ) {
			return new \WP_Error(
				'lvdl_lh_invalid_nonce',
				__( 'Invalid request token.', 'lvdl-little-hotelier' ),
				array( 'status' => 403 )
			);
		}

		$settings = $this->settings_repo->get_settings();

		$data = array(
			'checkInDate'  => sanitize_text_field( (string) $request->get_param( 'checkInDate' ) ),
			'checkOutDate' => sanitize_text_field( (string) $request->get_param( 'checkOutDate' ) ),
			'adults'       => (int) $request->get_param( 'adults' ),
			'children'     => (int) $request->get_param( 'children' ),
			'infants'      => (int) $request->get_param( 'infants' ),
			'promocode'    => sanitize_text_field( (string) $request->get_param( 'promocode' ) ),
			'currency'     => sanitize_text_field( (string) $request->get_param( 'currency' ) ),
			'locale'       => sanitize_text_field( (string) $request->get_param( 'locale' ) ),
			'trackPage'    => sanitize_text_field( (string) $request->get_param( 'trackPage' ) ),
			'channel_code' => sanitize_text_field( (string) $request->get_param( 'channel_code' ) ),
		);

		if ( '' === $data['currency'] ) {
			$data['currency'] = (string) $settings['currency'];
		}
		if ( '' === $data['locale'] ) {
			$data['locale'] = (string) $settings['locale'];
		}
		if ( '' === $data['trackPage'] ) {
			$data['trackPage'] = 'yes';
		}
		if ( '' === $data['channel_code'] ) {
			$data['channel_code'] = (string) $settings['channel_code'];
		}

		try {
			$booking_request = new BookingRequest(
				new DateRange( $data['checkInDate'], $data['checkOutDate'], absint( $settings['max_stay_days'] ) ),
				new GuestCounts( $data['adults'], $data['children'], $data['infants'] ),
				$data['currency'],
				$data['locale'],
				$data['promocode'],
				$data['trackPage'],
				$data['channel_code']
			);

			$redirect_url = $this->url_builder->build( (string) $settings['base_url_template'], $booking_request );

			return new \WP_REST_Response(
				array(
					'redirect_url' => esc_url_raw( $redirect_url ),
					'validated'    => true,
				),
				200
			);
		} catch ( ValidationException $e ) {
			return new \WP_Error(
				'lvdl_lh_validation_error',
				$e->getMessage(),
				array(
					'status'       => 400,
					'field_errors' => $e->get_field_errors(),
				)
			);
		}
	}

	/**
	 * @return \WP_REST_Response
	 */
	public function get_nonce(): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'nonce' => wp_create_nonce( 'lvdl_lh_booking_nonce' ),
			),
			200
		);
	}
}
